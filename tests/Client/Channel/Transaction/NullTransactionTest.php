<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Transaction;

use Innmind\AMQP\Client\Channel\{
    Transaction\NullTransaction,
    Transaction,
};
use PHPUnit\Framework\TestCase;

class NullTransactionTest extends TestCase
{
    public function testInterface()
    {
        $tx = new NullTransaction;

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertSame($tx, $tx->select());
        $this->assertSame($tx, $tx->commit());
        $this->assertSame($tx, $tx->rollback());
    }
}
