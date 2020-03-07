<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Transaction;

use Innmind\AMQP\Client\Channel\Transaction as TransactionInterface;

final class NullTransaction implements TransactionInterface
{
    public function select(): void
    {
        // pass
    }

    public function commit(): void
    {
        // pass
    }

    public function rollback(): void
    {
        // pass
    }
}
