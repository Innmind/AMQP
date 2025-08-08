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
    Attempt,
    Maybe,
};

/**
 * This class cannot have an immutable like behaviour as the signal is sent
 * asynchronously so we need to change a state
 */
final class SignalListener
{
    private bool $installed = false;
    private bool $notified = false;
    /** @var Maybe<Signal> */
    private Maybe $received;
    /** @var Maybe<Channel> */
    private Maybe $channel;
    /** @var Maybe<Signals> */
    private Maybe $signals;
    /** @var \Closure(Signal): void */
    private \Closure $softClose;
    private bool $closing = false;

    private function __construct()
    {
        /** @var Maybe<Signal> */
        $this->received = Maybe::nothing();
        /** @var Maybe<Channel> */
        $this->channel = Maybe::nothing();
        /** @var Maybe<Signals> */
        $this->signals = Maybe::nothing();
        $this->softClose = function(Signal $signal): void {
            // Do not re-attempt to close when already closing if the user sends
            // multiple signals.
            if ($this->closing) {
                return;
            }

            $this->notified = true;
            $this->received = Maybe::just($signal);
        };
    }

    public static function uninstalled(): self
    {
        return new self;
    }

    public function install(Signals $signals, Channel $channel): void
    {
        if (!$this->installed) {
            $signals->listen(Signal::hangup, static function() {
                // do nothing so it can run in background
            });
            $signals->listen(Signal::interrupt, $this->softClose);
            $signals->listen(Signal::abort, $this->softClose);
            $signals->listen(Signal::terminate, $this->softClose);
            $signals->listen(Signal::terminalStop, $this->softClose);
            $signals->listen(Signal::alarm, $this->softClose);
            $this->signals = Maybe::just($signals);
            $this->installed = true;
        }

        $this->channel = Maybe::just($channel);
    }

    public function uninstall(): void
    {
        $_ = $this->signals->match(
            fn($signals) => $signals->remove($this->softClose),
            static fn() => null,
        );
    }

    public function notified(): bool
    {
        // Return false when closing to avoid abort watching the socket during
        // the handshake to properly close the connection.
        if ($this->closing) {
            return false;
        }

        return $this->notified;
    }

    /**
     * @template T
     *
     * @param callable(): Attempt<T> $continue
     *
     * @return Attempt<T>
     */
    public function close(Connection $connection, callable $continue): Attempt
    {
        return Maybe::all($this->received, $this->channel)
            ->map(static fn(Signal $signal, Channel $channel) => [$signal, $channel])
            ->filter(fn() => !$this->closing)
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
                        ->mapError(Failure::as(Failure::toCloseChannel()))
                        ->flatMap(
                            static fn() => $connection
                                ->close()
                                ->mapError(Failure::as(Failure::toCloseConnection())),
                        )
                        ->flatMap(static fn() => Attempt::error(Failure::closedBySignal($signal)));
                },
                static fn() => $continue(),
            );
    }
}
