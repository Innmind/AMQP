<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Failure,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Channel\Close,
};
use Innmind\OperatingSystem\CurrentProcess\Signals;
use Innmind\Signals\Signal;
use Innmind\Immutable\{
    Set,
    Either,
    Maybe,
};

/**
 * This class cannot have an immutable like behaviour as the signal is sents
 * asynchronously so we need to change a state
 */
final class SignalListener
{
    private bool $installed = false;
    /** @var Maybe<Signal> */
    private Maybe $notified;
    /**
     * Even though our Client only support one channel per connection we use a
     * Set here in case womeone figures out how to work with multiple channels
     * on a single connection
     * @var Set<Channel>
     */
    private Set $channels;
    private bool $closing = false;

    private function __construct()
    {
        /** @var Maybe<Signal> */
        $this->notified = Maybe::nothing();
        /** @var Set<Channel> */
        $this->channels = Set::of();
    }

    public static function uninstalled(): self
    {
        return new self;
    }

    public function install(Signals $signals, Channel $channel): void
    {
        if (!$this->installed) {
            $softClose = function(Signal $signal): void {
                $this->notified = Maybe::just($signal);
            };
            $signals->listen(Signal::hangup, static function() {
                // do nothing so it can run in background
            });
            $signals->listen(Signal::interrupt, $softClose);
            $signals->listen(Signal::abort, $softClose);
            $signals->listen(Signal::terminate, $softClose);
            $signals->listen(Signal::terminalStop, $softClose);
            $signals->listen(Signal::alarm, $softClose);
            $this->installed = true;
        }

        $this->channels = ($this->channels)($channel);
    }

    /**
     * @return Either<Failure, Connection>
     */
    public function safe(Connection $connection): Either
    {
        return $this->notified->match(
            fn($signal) => $this->close($connection, $signal),
            static fn() => Either::right($connection),
        );
    }

    /**
     * @return Either<Failure, Connection>
     */
    private function close(Connection $connection, Signal $signal): Either
    {
        if ($this->closing) {
            return Either::right($connection);
        }

        $this->closing = true;

        $closed = $this
            ->channels
            ->reduce(
                Either::right($connection),
                $this->closeChannel(...),
            )
            ->flatMap(
                static fn($connection) => $connection
                    ->close()
                    ->either()
                    ->leftMap(static fn() => Failure::toCloseConnection())
                    ->flatMap(static fn() => Either::left(Failure::closedBySignal($signal))),
            );
        $this->closing = false; // technically this method should never be called twice

        return $closed;
    }

    /**
     * @param Either<Failure, Connection> $connection
     *
     * @return Either<Failure, Connection>
     */
    private function closeChannel(Either $connection, Channel $channel): Either
    {
        return $connection->flatMap(
            static fn($connection) => $connection
                ->request(
                    static fn($protocol) => $protocol->channel()->close(
                        $channel,
                        Close::demand(),
                    ),
                    Method::channelCloseOk,
                )
                ->map(static fn() => $connection)
                ->leftMap(static fn() => Failure::toCloseChannel()),
        );
    }
}
