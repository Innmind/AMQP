<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Client\State,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
};
use Innmind\Immutable\Either;

final class Transaction implements Command
{
    private Command $command;
    /** @var callable(mixed): bool */
    private $predicate;

    /**
     * @param callable(mixed): bool $predicate
     */
    private function __construct(Command $command, callable $predicate)
    {
        $this->command = $command;
        $this->predicate = $predicate;
    }

    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either {
        return $this
            ->select($connection, $channel)
            ->flatMap(fn($connection) => ($this->command)(
                $connection,
                $channel,
                $state,
            ))
            ->flatMap(fn($state) => $this->finish($state, $channel));
    }

    /**
     * @template A
     *
     * @param callable(A): bool $predicate If true it will commit otherwise it will rollback
     */
    public static function of(
        callable $predicate,
        Command $command,
    ): self {
        return new self($command, $predicate);
    }

    public function with(Command $command): self
    {
        return new self(
            new Pipe($this->command, $command),
            $this->predicate,
        );
    }

    /**
     * @return Either<Failure, Connection>
     */
    private function select(
        Connection $connection,
        Channel $channel,
    ): Either {
        /** @var Either<Failure, Connection> */
        return $connection
            ->send(static fn($protocol) => $protocol->transaction()->select($channel))
            ->wait(Method::transactionSelectOk)
            ->match(
                static fn($connection) => Either::right($connection),
                static fn($connection) => Either::right($connection),
                static fn() => Either::left(Failure::toSelect),
            );
    }

    /**
     * @return Either<Failure, State>
     */
    private function finish(State $state, Channel $channel): Either
    {
        return match (($this->predicate)($state->userState())) {
            true => $this->commit($state, $channel),
            false => $this->rollback($state, $channel),
        };
    }

    /**
     * @return Either<Failure, State>
     */
    private function commit(State $state, Channel $channel): Either
    {
        /** @var Either<Failure, State> */
        return $state
            ->connection()
            ->send(static fn($protocol) => $protocol->transaction()->commit($channel))
            ->wait(Method::transactionCommitOk)
            ->match(
                static fn($connection) => Either::right(State::of($connection, $state->userState())),
                static fn($connection) => Either::right(State::of($connection, $state->userState())),
                static fn() => Either::left(Failure::toCommit),
            );
    }

    /**
     * @return Either<Failure, State>
     */
    private function rollback(State $state, Channel $channel): Either
    {
        /** @var Either<Failure, State> */
        return $state
            ->connection()
            ->send(static fn($protocol) => $protocol->transaction()->rollback($channel))
            ->wait(Method::transactionRollbackOk)
            ->match(
                static fn($connection) => Either::right(State::of($connection, $state->userState())),
                static fn($connection) => Either::right(State::of($connection, $state->userState())),
                static fn() => Either::left(Failure::toRollback),
            );
    }
}
