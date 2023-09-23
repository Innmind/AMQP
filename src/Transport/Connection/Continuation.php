<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\ReceivedFrame,
    Transport\Frame,
    Transport\Frame\Method,
    Failure,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Maybe,
    Either,
};

/**
 * @internal
 */
final class Continuation
{
    /** @var Either<Failure, Connection|ReceivedFrame> */
    private Either $connection;

    /**
     * @param Either<Failure, Connection|ReceivedFrame> $connection
     */
    private function __construct(Either $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Either<Failure, Connection> $connection
     */
    public static function of(Either $connection): self
    {
        /** @psalm-suppress InvalidArgument Because it's always Connection */
        return new self($connection);
    }

    /**
     * @no-named-arguments
     */
    public function wait(Method ...$methods): self
    {
        /** @psalm-suppress InvalidArgument Because the right side is always ReceivedFrame */
        return new self($this->connection->flatMap(
            static fn($connection) => match (true) {
                $connection instanceof Connection => $connection->wait(...$methods),
                default => throw new LogicException("Can't call wait multiple times"),
            },
        ));
    }

    public function maybeWait(bool $wait, Method $method): self
    {
        if (!$wait) {
            return $this;
        }

        /** @psalm-suppress InvalidArgument Because the right side is always ReceivedFrame */
        return new self($this->connection->flatMap(
            static fn($connection) => match (true) {
                $connection instanceof Connection => $connection->wait($method),
                default => throw new LogicException("Can't call wait multiple times"),
            },
        ));
    }

    /**
     * @template R
     *
     * @param callable(Connection, Frame): Either<Failure, R> $withFrame
     * @param callable(Connection): R $withoutFrame
     *
     * @return Either<Failure, R>
     */
    public function then(callable $withFrame, callable $withoutFrame): Either
    {
        /** @psalm-suppress InvalidArgument Due to Either::right call */
        return $this
            ->connection
            ->flatMap(static fn($received) => match (true) {
                $received instanceof Connection => Either::right($withoutFrame($received)),
                default => $withFrame($received->connection(), $received->frame()),
            });
    }

    /**
     * @return Either<Failure, Connection>
     */
    public function connection(): Either
    {
        return $this->connection->map(
            static fn($received) => match (true) {
                $received instanceof Connection => $received,
                default => $received->connection(),
            },
        );
    }
}
