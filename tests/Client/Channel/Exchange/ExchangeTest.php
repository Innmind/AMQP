<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Exchange;

use Innmind\AMQP\{
    Client\Channel\Exchange\Exchange,
    Client\Channel\Exchange as ExchangeInterface,
    Transport\Connection\Connection,
    Transport\Protocol,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
    Model\Exchange\Type,
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

class ExchangeTest extends TestCase
{
    private $exchange;
    private $conncetion;

    public function setUp(): void
    {
        $this->exchange = new Exchange(
            $this->connection = new Connection(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5672/'),
                new Protocol(new Clock, $this->createMock(ArgumentTranslator::class)),
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
        $this->assertInstanceOf(ExchangeInterface::class, $this->exchange);
    }

    public function testDeclare()
    {
        $this->assertNull(
            $this->exchange->declare(
                Declaration::durable('foo', Type::direct)->dontWait(),
            ),
        );
        $this->assertNull(
            $this->exchange->declare(
                Declaration::durable('bar', Type::direct),
            ),
        );
    }

    public function testDelete()
    {
        $this->assertNull(
            $this->exchange->delete(
                (new Deletion('foo'))->dontWait(),
            ),
        );
        $this->assertNull(
            $this->exchange->delete(
                new Deletion('bar'),
            ),
        );
    }
}
