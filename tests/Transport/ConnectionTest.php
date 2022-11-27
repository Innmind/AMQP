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
    Failure,
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
use Innmind\Immutable\{
    Sequence,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testInterface()
    {
        $connection = Connection::open(
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
        $this->assertInstanceOf(Frame::class, $connection->wait(Method::channelOpenOk)->match(
            static fn($received) => $received->frame(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            SideEffect::class,
            $connection->close()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        ); //test it closes without exception
    }

    public function testClose()
    {
        $connection = Connection::open(
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
        $this->assertInstanceOf(SideEffect::class, $connection->close()->match(
            static fn($sideEffect) => $sideEffect,
            static fn() => null,
        ));
        $this->assertTrue($connection->closed());
    }

    public function testReturnFailureWhenReceivedFrameIsNotTheExpectedOne()
    {
        $connection = Connection::open(
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

        $this->assertSame(
            Failure::unexpectedFrame,
            $connection
                ->send(static fn($protocol) => $protocol->channel()->open(new Channel(2)))
                ->wait(Method::connectionOpen)
                ->either()
                ->match(
                    static fn() => null,
                    static fn($failure) => $failure,
                ),
        );
    }

    public function testReturnFailureWhenConnectionClosedByServer()
    {
        $connection = Connection::open(
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

        $connection = $connection->send(static fn() => Sequence::of(Frame::method(
            new Channel(0),
            Method::of(20, 10),
            //missing arguments
        )))->match(
            static fn($connection) => $connection,
            static fn($connection) => $connection,
            static fn() => null,
        );
        $this->assertSame(
            Failure::closedByServer,
            $connection->wait(Method::channelOpenOk)->match(
                static fn() => null,
                static fn($failure) => $failure,
            ),
        );
    }
}
