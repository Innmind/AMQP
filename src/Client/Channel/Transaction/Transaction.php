<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Transaction;

use Innmind\AMQP\{
    Client\Channel\Transaction as TransactionInterface,
    Transport\Connection,
    Transport\Frame\Channel,
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
        $this->connection->send($this->connection->protocol()->transaction()->select(
            $this->channel
        ));
        $this->connection->wait('tx.select-ok');
    }

    public function commit(): void
    {
        $this->connection->send($this->connection->protocol()->transaction()->commit(
            $this->channel
        ));
        $this->connection->wait('tx.commit-ok');
    }

    public function rollback(): void
    {
        $this->connection->send($this->connection->protocol()->transaction()->rollback(
            $this->channel
        ));
        $this->connection->wait('tx.rollback-ok');
    }
}
