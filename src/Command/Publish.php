<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Transport\Connection,
    Transport\Frame\Channel,
    Model\Basic\Publish as Model,
};
use Innmind\Immutable\{
    Either,
    Sequence,
};

final class Publish implements Command
{
    /** @var Sequence<Model> */
    private Sequence $commands;

    /**
     * @param Sequence<Model> $commands
     */
    private function __construct(Sequence $commands)
    {
        $this->commands = $commands;
    }

    /**
     * @template S
     *
     * @param S $state
     *
     * @return Either<Failure, array{Connection, S}>
     */
    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var Either<Failure, array{Connection, S}>
         */
        return $this
            ->commands
            ->reduce(
                Either::right($connection),
                fn(Either $connection, $command) => $connection->flatMap(
                    fn(Connection $connection) => $this->publish($connection, $channel, $command),
                ),
            )
            ->map(static fn($connection) => [$connection, $state]);
    }

    public static function one(Model $command): self
    {
        return new self(Sequence::of($command));
    }

    /**
     * @param Sequence<Model> $commands
     */
    public static function many(Sequence $commands): self
    {
        return new self($commands);
    }

    /**
     * @return Either<Failure, Connection>
     */
    private function publish(
        Connection $connection,
        Channel $channel,
        Model $command,
    ): Either {
        /** @var Either<Failure, Connection> */
        return $connection
            ->send(static fn($protocol, $maxFrameSize) => $protocol->basic()->publish(
                $channel,
                $command,
                $maxFrameSize,
            ))
            ->match(
                static fn($connection) => Either::right($connection),
                static fn($connection) => Either::right($connection),
                static fn() => Either::left(Failure::toPublish),
            );
    }
}
