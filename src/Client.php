<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Channel\Close as CloseChannel,
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

    /**
     * @param Maybe<Command> $command
     * @param callable(): Maybe<Connection> $load
     */
    private function __construct(Maybe $command, callable $load)
    {
        $this->command = $command;
        $this->load = $load;
    }

    /**
     * @param callable(): Maybe<Connection> $load
     */
    public static function of(callable $load): self
    {
        /** @var Maybe<Command> */
        $command = Maybe::nothing();

        return new self($command, $load);
    }

    public function with(Command $command): self
    {
        return new self(
            $this
                ->command
                ->map(static fn($previous) => new Command\Pipe($previous, $command))
                ->otherwise(static fn() => Maybe::just($command)),
            $this->load,
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

                    return $command($connection, $channel, $state)->flatMap(
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
            ->leftMap(static fn() => Failure::toOpenConnection)
            ->map(static fn($connection) => $connection->send(
                static fn($protocol) => $protocol->channel()->open($channel),
            ))
            ->map(static fn($continuation) => $continuation->wait(Method::channelOpenOk))
            ->flatMap(
                static fn($continuation) => $continuation
                    ->either()
                    ->map(static fn($connection) => [$connection, $channel])
                    ->leftMap(static fn() => Failure::toOpenChannel),
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
            ->either()
            ->leftMap(static fn() => Failure::toCloseChannel)
            ->flatMap(
                static fn($connection) => $connection
                    ->close()
                    ->either()
                    ->leftMap(static fn() => Failure::toCloseConnection),
            );
    }
}
