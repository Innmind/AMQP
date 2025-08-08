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
use Innmind\Immutable\Attempt;

final class Transaction implements Command
{
    /**
     * @param \Closure(mixed): bool $predicate
     */
    private function __construct(
        private Command $command,
        private \Closure $predicate,
    ) {
    }

    #[\Override]
    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
    ): Attempt {
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
    #[\NoDiscard]
    public static function of(
        callable $predicate,
        Command $command,
    ): self {
        return new self($command, \Closure::fromCallable($predicate));
    }

    #[\NoDiscard]
    public function with(Command $command): self
    {
        return new self(
            new Pipe($this->command, $command),
            $this->predicate,
        );
    }

    /**
     * @return Attempt<Connection>
     */
    private function select(
        Connection $connection,
        Channel $channel,
    ): Attempt {
        return $connection
            ->request(
                static fn($protocol) => $protocol->transaction()->select($channel),
                Method::transactionSelectOk,
            )
            ->map(static fn() => $connection)
            ->mapError(Failure::as(Failure::toSelect()));
    }

    /**
     * @return Attempt<State>
     */
    private function finish(
        Connection $connection,
        Channel $channel,
        State $state,
    ): Attempt {
        return match (($this->predicate)($state->unwrap())) {
            true => $this->commit($connection, $channel, $state),
            false => $this->rollback($connection, $channel, $state),
        };
    }

    /**
     * @return Attempt<State>
     */
    private function commit(
        Connection $connection,
        Channel $channel,
        State $state,
    ): Attempt {
        return $connection
            ->request(
                static fn($protocol) => $protocol->transaction()->commit($channel),
                Method::transactionCommitOk,
            )
            ->map(static fn() => $state)
            ->mapError(Failure::as(Failure::toCommit()));
    }

    /**
     * @return Attempt<State>
     */
    private function rollback(
        Connection $connection,
        Channel $channel,
        State $state,
    ): Attempt {
        return $connection
            ->request(
                static fn($protocol) => $protocol->transaction()->rollback($channel),
                Method::transactionRollbackOk,
            )
            ->map(static fn() => $state)
            ->mapError(Failure::as(Failure::toRollback()));
    }
}
