<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Transaction;

use Innmind\AMQP\{
    Client\Channel\Transaction\Transaction,
    Client\Channel\Transaction as TransactionInterface,
    Transport\Connection\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Protocol,
    Transport\Protocol\ArgumentTranslator,
    Model\Channel\Close,
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\Earth\{
    ElapsedPeriod,
    Clock,
};
use Innmind\Url\Url;
use Innmind\OperatingSystem\{
    Remote,
    Sockets,
};
use Innmind\Server\Control\Server;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    private $transaction;
    private $connection;

    public function setUp(): void
    {
        $this->transaction = new Transaction(
            $this->connection = new Connection(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5672/'),
                new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Clock,
                Remote\Generic::of($this->createMock(Server::class), new Clock),
                Sockets\Unix::of(),
            ),
            new Channel(1),
        );
        $this->connection->send(
            $this->connection->protocol()->channel()->open(new Channel(1)),
        );
        $this->connection->wait(Method::channelOpenOk);
    }

    public function tearDown(): void
    {
        $this->connection->send(
            $this->connection->protocol()->channel()->close(
                new Channel(1),
                new Close,
            ),
        );
        $this->connection->wait(Method::channelCloseOk);
        $this->connection->close();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(TransactionInterface::class, $this->transaction);
    }

    public function testSelect()
    {
        $this->assertNull($this->transaction->select());
    }

    public function testCommit()
    {
        $this->transaction->select();
        $this->assertNull($this->transaction->commit());
    }

    public function testRollback()
    {
        $this->transaction->select();
        $this->assertNull($this->transaction->rollback());
    }
}
