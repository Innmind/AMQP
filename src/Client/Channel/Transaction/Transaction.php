<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Transaction;

use Innmind\AMQP\{
    Client\Channel\Transaction as TransactionInterface,
    Transport\Connection,
    Transport\Frame\Channel
};

final class Transaction implements TransactionInterface
{
    private $connection;
    private $channel;

    public function __construct(Connection $connection, Channel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
    }

    public function select(): TransactionInterface
    {
        $this
            ->connection
            ->send($this->connection->protocol()->transaction()->select(
                $this->channel
            ))
            ->wait('tx.select-ok');

        return $this;
    }

    public function commit(): TransactionInterface
    {
        $this
            ->connection
            ->send($this->connection->protocol()->transaction()->commit(
                $this->channel
            ))
            ->wait('tx.commit-ok');

        return $this;
    }

    public function rollback(): TransactionInterface
    {
        $this
            ->connection
            ->send($this->connection->protocol()->transaction()->rollback(
                $this->channel
            ))
            ->wait('tx.rollback-ok');

        return $this;
    }
}
