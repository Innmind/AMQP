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
    Either,
    Maybe,
    SideEffect,
};

final class Client
{
    /** @var Maybe<Command> */
    private Maybe $command;
    /** @var callable(): Maybe<Connection> */
    private $load;
    private Filesystem $filesystem;
    /** @var Maybe<CurrentProcess> */
    private Maybe $signals;

    /**
     * @param Maybe<Command> $command
     * @param callable(): Maybe<Connection> $load
     * @param Maybe<CurrentProcess> $signals
     */
    private function __construct(
        Maybe $command,
        callable $load,
        Filesystem $filesystem,
        Maybe $signals,
    ) {
        $this->command = $command;
        $this->load = $load;
        $this->filesystem = $filesystem;
        $this->signals = $signals;
    }

    /**
     * @param callable(): Maybe<Connection> $load
     */
    public static function of(callable $load, Filesystem $filesystem): self
    {
        /** @var Maybe<Command> */
        $command = Maybe::nothing();
        /** @var Maybe<CurrentProcess> */
        $signals = Maybe::nothing();

        return new self(
            $command,
            $load,
            $filesystem,
            $signals,
        );
    }

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
     * @return Either<Failure, T>
     */
    public function run(mixed $state): Either
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
            static fn() => Either::right($state),
        );
    }

    /**
     * @return Either<Failure, array{Connection, Channel}>
     */
    private function openChannel(): Either
    {
        // Since the connection is never shared between objects then there is no
        // need to have a dynamic channel number as there will ALWAYS be one
        // channel per connection
        $channel = new Channel(1);

        /** @var Either<Failure, array{Connection, Channel}> */
        return ($this->load)()
            ->either()
            ->leftMap(static fn() => Failure::toOpenConnection())
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
                    ->leftMap(static fn() => Failure::toOpenChannel()),
            );
    }

    /**
     * @return Either<Failure, SideEffect>
     */
    private function close(Connection $connection, Channel $channel): Either
    {
        /** @var Either<Failure, SideEffect> */
        return $connection
            ->request(
                static fn($protocol) => $protocol->channel()->close(
                    $channel,
                    CloseChannel::demand(),
                ),
                Method::channelCloseOk,
            )
            ->leftMap(static fn() => Failure::toCloseChannel())
            ->flatMap(
                static fn() => $connection
                    ->close()
                    ->either()
                    ->leftMap(static fn() => Failure::toCloseConnection()),
            );
    }
}
