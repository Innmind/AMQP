<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Channel\Close as CloseChannel,
};
use Innmind\OperatingSystem\{
    CurrentProcess,
    Filesystem,
};
use Innmind\Immutable\{
    Attempt,
    Maybe,
    SideEffect,
};

final class Client
{
    /**
     * @param Maybe<Command> $command
     * @param \Closure(): Attempt<Connection> $load
     * @param Maybe<CurrentProcess> $signals
     */
    private function __construct(
        private Maybe $command,
        private \Closure $load,
        private Filesystem $filesystem,
        private Maybe $signals,
    ) {
    }

    /**
     * @param callable(): Attempt<Connection> $load
     */
    #[\NoDiscard]
    public static function of(callable $load, Filesystem $filesystem): self
    {
        /** @var Maybe<Command> */
        $command = Maybe::nothing();
        /** @var Maybe<CurrentProcess> */
        $signals = Maybe::nothing();

        return new self(
            $command,
            \Closure::fromCallable($load),
            $filesystem,
            $signals,
        );
    }

    #[\NoDiscard]
    public function with(Command $command): self
    {
        return new self(
            $this
                ->command
                ->map(static fn($previous) => new Command\Pipe($previous, $command))
                ->otherwise(static fn() => Maybe::just($command)),
            $this->load,
            $this->filesystem,
            $this->signals,
        );
    }

    #[\NoDiscard]
    public function listenSignals(CurrentProcess $currentProcess): self
    {
        return new self(
            $this->command,
            $this->load,
            $this->filesystem,
            Maybe::just($currentProcess),
        );
    }

    /**
     * @template T
     *
     * @param T $state
     *
     * @return Attempt<T>
     */
    #[\NoDiscard]
    public function run(mixed $state): Attempt
    {
        return $this->command->match(
            fn($command) => $this
                ->openChannel()
                ->flatMap(function($in) use ($command, $state) {
                    [$connection, $channel] = $in;
                    $read = MessageReader::of($this->filesystem);

                    return $command($connection, $channel, $read, Client\State::of($state))->flatMap(
                        fn($state) => $this
                            ->close($connection, $channel)
                            ->map(static fn(): mixed => $state->unwrap()),
                    );
                }),
            static fn() => Attempt::result($state),
        );
    }

    /**
     * @return Attempt<array{Connection, Channel}>
     */
    private function openChannel(): Attempt
    {
        // Since the connection is never shared between objects then there is no
        // need to have a dynamic channel number as there will ALWAYS be one
        // channel per connection
        $channel = new Channel(1);

        return ($this->load)()
            ->mapError(Failure::as(Failure::toOpenConnection()))
            ->flatMap(
                fn($connection) => $connection
                    ->request(
                        static fn($protocol) => $protocol->channel()->open($channel),
                        Method::channelOpenOk,
                    )
                    ->map(fn() => $this->signals->match(
                        static fn($process) => $connection->listenSignals(
                            $process->signals(),
                            $channel,
                        ),
                        static fn() => null,
                    ))
                    ->map(static fn() => [$connection, $channel])
                    ->mapError(Failure::as(Failure::toOpenChannel())),
            );
    }

    /**
     * @return Attempt<SideEffect>
     */
    private function close(Connection $connection, Channel $channel): Attempt
    {
        return $connection
            ->request(
                static fn($protocol) => $protocol->channel()->close(
                    $channel,
                    CloseChannel::demand(),
                ),
                Method::channelCloseOk,
            )
            ->mapError(Failure::as(Failure::toCloseChannel()))
            ->flatMap(
                static fn() => $connection
                    ->close()
                    ->mapError(Failure::as(Failure::toCloseConnection())),
            );
    }
}
