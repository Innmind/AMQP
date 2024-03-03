<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Client\State,
    Transport\Connection,
    Transport\Connection\MessageReader,
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
        MessageReader $read,
        State $state,
    ): Either {
        return $this
            ->select($connection, $channel)
            ->flatMap(fn($connection) => ($this->command)(
                $connection,
                $channel,
                $read,
                $state,
            ))
            ->flatMap(fn($state) => $this->finish(
                $connection,
                $channel,
                $state,
            ));
    }

    /**
     * @param callable(mixed): bool $predicate If true it will commit otherwise it will rollback
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
        return $connection
            ->request(
                static fn($protocol) => $protocol->transaction()->select($channel),
                Method::transactionSelectOk,
            )
            ->map(static fn() => $connection)
            ->leftMap(static fn() => Failure::toSelect());
    }

    /**
     * @return Either<Failure, State>
     */
    private function finish(
        Connection $connection,
        Channel $channel,
        State $state,
    ): Either {
        return match (($this->predicate)($state->userState())) {
            true => $this->commit($connection, $channel, $state),
            false => $this->rollback($connection, $channel, $state),
        };
    }

    /**
     * @return Either<Failure, State>
     */
    private function commit(
        Connection $connection,
        Channel $channel,
        State $state,
    ): Either {
        return $connection
            ->request(
                static fn($protocol) => $protocol->transaction()->commit($channel),
                Method::transactionCommitOk,
            )
            ->map(static fn() => $state)
            ->leftMap(static fn() => Failure::toCommit());
    }

    /**
     * @return Either<Failure, State>
     */
    private function rollback(
        Connection $connection,
        Channel $channel,
        State $state,
    ): Either {
        return $connection
            ->request(
                static fn($protocol) => $protocol->transaction()->rollback($channel),
                Method::transactionRollbackOk,
            )
            ->map(static fn() => $state)
            ->leftMap(static fn() => Failure::toRollback());
    }
}
