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
    Either,
    Maybe,
    SideEffect,
};

/**
 * This class cannot have an immutable like behaviour as the signal is sent
 * asynchronously so we need to change a state
 */
final class SignalListener
{
    private bool $installed = false;
    /** @var Maybe<Signal> */
    private Maybe $notified;
    /** @var Maybe<Channel> */
    private Maybe $channel;
    private bool $closing = false;

    private function __construct()
    {
        /** @var Maybe<Signal> */
        $this->notified = Maybe::nothing();
        /** @var Maybe<Channel> */
        $this->channel = Maybe::nothing();
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

        $this->channel = Maybe::just($channel);
    }

    /**
     * @param callable(): Either<Failure, SideEffect> $continue
     *
     * @return Either<Failure, SideEffect>
     */
    public function match(callable $continue, Connection $connection): Either
    {
        return Maybe::all($this->notified, $this->channel)
            ->map(static fn(Signal $signal, Channel $channel) => [$signal, $channel])
            ->filter(fn() => !$this->closing)
            ->either()
            ->match(
                function($in) use ($connection) {
                    $this->closing = true;
                    [$signal, $channel] = $in;

                    return $connection
                        ->request(
                            static fn($protocol) => $protocol->channel()->close(
                                $channel,
                                Close::demand(),
                            ),
                            Method::channelCloseOk,
                        )
                        ->leftMap(static fn() => Failure::toCloseChannel())
                        ->flatMap(
                            static fn() => $connection
                                ->close()
                                ->either()
                                ->leftMap(static fn() => Failure::toCloseConnection()),
                        )
                        ->flatMap(static fn() => Either::left(Failure::closedBySignal($signal)));
                },
                static fn() => $continue(),
            );
    }
}
