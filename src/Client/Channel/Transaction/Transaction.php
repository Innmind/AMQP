<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Transaction;

use Innmind\AMQP\{
    Client\Channel\Transaction as TransactionInterface,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
};

final class Transaction implements TransactionInterface
{
    private Connection $connection;
    private Channel $channel;

    public function __construct(Connection $connection, Channel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
    }

    public function select(): void
    {
        $_ = $this
            ->connection
            ->send(fn($protocol) => $protocol->transaction()->select(
                $this->channel,
            ))
            ->wait(Method::transactionSelectOk)
            ->match(
                static fn() => null,
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }

    public function commit(): void
    {
        $_ = $this
            ->connection
            ->send(fn($protocol) => $protocol->transaction()->commit(
                $this->channel,
            ))
            ->wait(Method::transactionCommitOk)
            ->match(
                static fn() => null,
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }

    public function rollback(): void
    {
        $_ = $this
            ->connection
            ->send(fn($protocol) => $protocol->transaction()->rollback(
                $this->channel,
            ))
            ->wait(Method::transactionRollbackOk)
            ->match(
                static fn() => null,
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }
}
