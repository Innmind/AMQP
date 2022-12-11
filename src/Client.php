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
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\Stream\Streams;
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
    private Streams $streams;
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
        Streams $streams,
        Maybe $signals,
    ) {
        $this->command = $command;
        $this->load = $load;
        $this->streams = $streams;
        $this->signals = $signals;
    }

    /**
     * @param callable(): Maybe<Connection> $load
     */
    public static function of(callable $load, Streams $streams = null): self
    {
        /** @var Maybe<Command> */
        $command = Maybe::nothing();
        /** @var Maybe<CurrentProcess> */
        $signals = Maybe::nothing();

        return new self($command, $load, $streams ?? Streams::of(), $signals);
    }

    public function with(Command $command): self
    {
        return new self(
            $this
                ->command
                ->map(static fn($previous) => new Command\Pipe($previous, $command))
                ->otherwise(static fn() => Maybe::just($command)),
            $this->load,
            $this->streams,
            $this->signals,
        );
    }

    public function listenSignals(CurrentProcess $currentProcess): self
    {
        // We ask for the current process instead of the signals wrapper directly
        // because the user may fork the process between the time this method is
        // called and the time the listeners are installed (when run is called).
        // This would result on the listeners being installed for the parent
        // process instead of the child.
        return new self(
            $this->command,
            $this->load,
            $this->streams,
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
                    $read = MessageReader::of($this->streams);

                    return $command($connection, $channel, $read, $state)->flatMap(
                        fn($clientState) => $this
                            ->close($clientState->connection(), $channel)
                            ->map(static fn(): mixed => $clientState->userState()),
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
            ->map(static fn($connection) => $connection->send(
                static fn($protocol) => $protocol->channel()->open($channel),
            ))
            ->map(static fn($continuation) => $continuation->wait(Method::channelOpenOk))
            ->flatMap(
                fn($continuation) => $continuation
                    ->connection()
                    ->map(fn($connection) => $this->signals->match(
                        static fn($process) => $connection->listenSignals(
                            $process->signals(),
                            $channel,
                        ),
                        static fn() => $connection,
                    ))
                    ->map(static fn($connection) => [$connection, $channel])
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
            ->send(static fn($protocol) => $protocol->channel()->close(
                $channel,
                CloseChannel::demand(),
            ))
            ->wait(Method::channelCloseOk)
            ->connection()
            ->leftMap(static fn() => Failure::toCloseChannel())
            ->flatMap(
                static fn($connection) => $connection
                    ->close()
                    ->either()
                    ->leftMap(static fn() => Failure::toCloseConnection()),
            );
    }
}
