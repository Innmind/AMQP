<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Connection,
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
use Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testInterface()
    {
        $connection = Connection::of(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol(new Clock, $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            Remote\Generic::of($this->createMock(Server::class), new Clock),
            Sockets\Unix::of(),
        )->match(
            static fn($connection) => $connection,
            static fn() => null,
        );

        $this->assertSame(
            $connection,
            $connection
                ->send(
                    static fn($protocol) => $protocol->channel()->open(new Channel(1)),
                )
                ->match(
                    static fn($connection) => $connection,
                    static fn($connection) => $connection,
                    static fn() => null,
                ),
        );
        $this->assertInstanceOf(Frame::class, $connection->wait(Method::channelOpenOk));
        $connection->close(); //test it closes without exception
    }

    public function testClose()
    {
        $connection = Connection::of(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol(new Clock, $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            Remote\Generic::of($this->createMock(Server::class), new Clock),
            Sockets\Unix::of(),
        )->match(
            static fn($connection) => $connection,
            static fn() => null,
        );

        $this->assertFalse($connection->closed());
        $this->assertNull($connection->close());
        $this->assertTrue($connection->closed());
    }

    public function testThrowWhenReceivedFrameIsNotTheExpectedOne()
    {
        $connection = Connection::of(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            new Protocol(new Clock, $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            Remote\Generic::of($this->createMock(Server::class), new Clock),
            Sockets\Unix::of(),
        )->match(
            static fn($connection) => $connection,
            static fn() => null,
        );

        $this->expectException(UnexpectedFrame::class);

        $connection->send(static fn($protocol) => $protocol->channel()->open(new Channel(2)));
        $connection->wait(Method::connectionOpen);
    }

    public function testThrowWhenConnectionClosedByServer()
    {
        $connection = Connection::of(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol(new Clock, $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            Remote\Generic::of($this->createMock(Server::class), new Clock),
            Sockets\Unix::of(),
        )->match(
            static fn($connection) => $connection,
            static fn() => null,
        );

        try {
            $connection->send(static fn() => Sequence::of(Frame::method(
                new Channel(0),
                Method::of(20, 10),
                //missing arguments
            )));
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
