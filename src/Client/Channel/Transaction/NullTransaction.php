<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Transaction;

use Innmind\AMQP\Client\Channel\Transaction as TransactionInterface;

final class NullTransaction implements TransactionInterface
{
    public function select(): TransactionInterface
    {
        return $this;
    }

    public function commit(): TransactionInterface
    {
        return $this;
    }

    public function rollback(): TransactionInterface
    {
        return $this;
    }
}
