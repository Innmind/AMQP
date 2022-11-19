<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\Transport\{
    Connection,
    Frame,
    Frame\Method,
};
use Innmind\Immutable\Maybe;

final class Continuation
{
    /** @var Maybe<Connection> */
    private Maybe $connection;
    /** @var Maybe<Frame> */
    private Maybe $frame;

    /**
     * @param Maybe<Connection> $connection
     * @param Maybe<Frame> $frame
     */
    private function __construct(Maybe $connection, Maybe $frame)
    {
        $this->connection = $connection;
        $this->frame = $frame;
    }

    /**
     * @param Maybe<Connection> $connection
     */
    public static function of(Maybe $connection): self
    {
        /** @var Maybe<Frame> */
        $frame = Maybe::nothing();

        return new self($connection, $frame);
    }

    /**
     * @no-named-arguments
     */
    public function wait(Method ...$methods): self
    {
        return new self(
            $this->connection,
            $this->connection->map(
                static fn($connection) => $connection->wait(...$methods),
            ),
        );
    }

    public function maybeWait(bool $wait, Method $method): self
    {
        if (!$wait) {
            return $this;
        }

        return new self(
            $this->connection,
            $this->connection->map(
                static fn($connection) => $connection->wait($method),
            ),
        );
    }

    /**
     * @template A
     * @template B
     * @template C
     *
     * @param callable(Connection, Frame): A $withFrame
     * @param callable(Connection): B $withoutFrame
     * @param callable(): C $error
     *
     * @return A|B|C
     */
    public function match(
        callable $withFrame,
        callable $withoutFrame,
        callable $error,
    ) {
        return $this->connection->match(
            fn($connection) => $this->frame->match(
                static fn($frame) => $withFrame($connection, $frame),
                static fn() => $withoutFrame($connection),
            ),
            $error,
        );
    }
}
