<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Received,
    Transport\Frame,
    Transport\Frame\Method,
    Failure,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Maybe,
    Either,
};

final class Continuation
{
    /** @var Either<Failure, Connection|Received> */
    private Either $connection;

    /**
     * @param Either<Failure, Connection|Received> $connection
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
        /** @psalm-suppress InvalidArgument Because the right side is always Received */
        return new self($this->connection->flatMap(
            static fn($connection) => match ($connection instanceof Connection) {
                true => $connection->wait(...$methods),
                false => throw new LogicException("Can't call wait multiple time"),
            },
        ));
    }

    public function maybeWait(bool $wait, Method $method): self
    {
        if (!$wait) {
            return $this;
        }

        /** @psalm-suppress InvalidArgument Because the right side is always Received */
        return new self($this->connection->flatMap(
            static fn($connection) => match ($connection instanceof Connection) {
                true => $connection->wait($method),
                false => throw new LogicException("Can't call wait multiple time"),
            },
        ));
    }

    /**
     * @template R
     *
     * @param callable(Connection, Frame): Maybe<R> $withFrame
     * @param callable(Connection): R $withoutFrame
     *
     * @return Maybe<R>
     */
    public function then(callable $withFrame, callable $withoutFrame): Maybe
    {
        return $this
            ->connection
            ->maybe()
            ->flatMap(static fn($received) => match ($received instanceof Connection) {
                true => Maybe::just($withoutFrame($received)),
                false => $withFrame($received->connection(), $received->frame()),
            });
    }

    /**
     * @return Either<Failure, Connection>
     */
    public function either(): Either
    {
        return $this->connection->map(
            static fn($received) => match ($received instanceof Connection) {
                true => $received,
                false => $received->connection(),
            },
        );
    }

    /**
     * @template A
     * @template B
     * @template C
     *
     * @param callable(Connection, Frame): A $withFrame
     * @param callable(Connection): B $withoutFrame
     * @param callable(Failure): C $error
     *
     * @return A|B|C
     */
    public function match(
        callable $withFrame,
        callable $withoutFrame,
        callable $error,
    ) {
        return $this->connection->match(
            static fn($received) => match ($received instanceof Connection) {
                true => $withoutFrame($received),
                false => $withFrame($received->connection(), $received->frame()),
            },
            $error,
        );
    }
}
