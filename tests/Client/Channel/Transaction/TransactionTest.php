<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Transaction;

use Innmind\AMQP\{
    Client\Channel\Transaction\Transaction,
    Client\Channel\Transaction as TransactionInterface,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Protocol\v091\Protocol,
    Transport\Protocol\ArgumentTranslator
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    private $transaction;
    private $connection;

    public function setUp()
    {
        $this->transaction = new Transaction(
            $this->connection = new Connection(
                Transport::tcp(),
                Url::fromString('//guest:guest@localhost:5672/'),
                new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000)
            ),
            new Channel(1)
        );
        $this->connection
            ->send(
                $this->connection->protocol()->channel()->open(new Channel(1))
            )
            ->wait('channel.open-ok');
    }

    public function tearDown()
    {
        $this->connection->close();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(TransactionInterface::class, $this->transaction);
    }

    public function testSelect()
    {
        $this->assertSame($this->transaction, $this->transaction->select());
    }

    public function testCommit()
    {
        $this->transaction->select();
        $this->assertSame($this->transaction, $this->transaction->commit());
    }

    public function testRollback()
    {
        $this->transaction->select();
        $this->assertSame($this->transaction, $this->transaction->rollback());
    }
}