<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Connection\Connection,
    Transport\Connection as ConnectionInterface,
    Transport\Protocol,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Connection\MaxFrameSize,
    Exception\VersionNotUsable,
    Exception\ConnectionClosed,
    Exception\UnexpectedFrame,
};
use Innmind\Socket\Internet\Transport;
use Innmind\Url\Url;
use Innmind\TimeContinuum\Earth\{
    ElapsedPeriod,
    Clock,
};
use Innmind\OperatingSystem\{
    Remote,
    Sockets,
};
use Innmind\Server\Control\Server;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testInterface()
    {
        $connection = new Connection(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol(new Clock, $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            Remote\Generic::of($this->createMock(Server::class), new Clock),
            Sockets\Unix::of(),
        );

        $this->assertInstanceOf(ConnectionInterface::class, $connection);
        $this->assertSame($protocol, $connection->protocol());
        $this->assertInstanceOf(MaxFrameSize::class, $connection->maxFrameSize());
        $this->assertSame(131072, $connection->maxFrameSize()->toInt());
        $this->assertNull(
            $connection->send(
                $protocol->channel()->open(new Channel(1)),
            ),
        );
        $this->assertInstanceOf(Frame::class, $connection->wait(Method::channelOpenOk));
        $connection->close(); //test it closes without exception
    }

    public function testClose()
    {
        $connection = new Connection(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol(new Clock, $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            Remote\Generic::of($this->createMock(Server::class), new Clock),
            Sockets\Unix::of(),
        );

        $this->assertFalse($connection->closed());
        $this->assertNull($connection->close());
        $this->assertTrue($connection->closed());
    }

    public function testThrowWhenReceivedFrameIsNotTheExpectedOne()
    {
        $connection = new Connection(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol(new Clock, $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            Remote\Generic::of($this->createMock(Server::class), new Clock),
            Sockets\Unix::of(),
        );

        $this->expectException(UnexpectedFrame::class);

        $connection->send($protocol->channel()->open(new Channel(2)));
        $connection->wait(Method::connectionOpen);
    }

    public function testThrowWhenConnectionClosedByServer()
    {
        $connection = new Connection(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol(new Clock, $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            Remote\Generic::of($this->createMock(Server::class), new Clock),
            Sockets\Unix::of(),
        );

        try {
            $connection->send(Frame::method(
                new Channel(0),
                Method::of(20, 10),
                //missing arguments
            ));
            $connection->wait(Method::channelOpenOk);
        } catch (ConnectionClosed $e) {
            $this->assertTrue($connection->closed());
            $this->assertSame('INTERNAL_ERROR', $e->getMessage());
            $this->assertSame(541, $e->getCode());
            $this->assertNull($e->cause()->match(
                static fn($method) => $method,
                static fn() => null,
            ));
        }
    }
}
