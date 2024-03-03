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
    Failure,
};
use Innmind\Socket\Internet\Transport;
use Innmind\Url\Url;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\OperatingSystem\Factory;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testInterface()
    {
        $os = Factory::build();
        $connection = Connection::open(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol($os->clock(), $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            $os->clock(),
            $os->remote(),
            $os->sockets(),
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
                ->connection()
                ->match(
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
        $os = Factory::build();
        $connection = Connection::open(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol($os->clock(), $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            $os->clock(),
            $os->remote(),
            $os->sockets(),
        )->match(
            static fn($connection) => $connection,
            static fn() => null,
        );

        $this->assertInstanceOf(SideEffect::class, $connection->close()->match(
            static fn($sideEffect) => $sideEffect,
            static fn() => null,
        ));
    }

    public function testReturnFailureWhenReceivedFrameIsNotTheExpectedOne()
    {
        $os = Factory::build();
        $connection = Connection::open(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            new Protocol($os->clock(), $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            $os->clock(),
            $os->remote(),
            $os->sockets(),
        )->match(
            static fn($connection) => $connection,
            static fn() => null,
        );

        $this->assertSame(
            Failure\Kind::unexpectedFrame,
            $connection
                ->send(static fn($protocol) => $protocol->channel()->open(new Channel(2)))
                ->wait(Method::connectionOpen)
                ->connection()
                ->match(
                    static fn() => null,
                    static fn($failure) => $failure->kind(),
                ),
        );
    }

    public function testReturnFailureWhenConnectionClosedByServer()
    {
        $os = Factory::build();
        $connection = Connection::open(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol($os->clock(), $this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            $os->clock(),
            $os->remote(),
            $os->sockets(),
        )->match(
            static fn($connection) => $connection,
            static fn() => null,
        );

        $connection = $connection->send(static fn() => Sequence::of(Frame::method(
            new Channel(0),
            Method::of(20, 10),
            //missing arguments
        )))
            ->connection()
            ->match(
                static fn($connection) => $connection,
                static fn() => null,
            );
        $this->assertSame(
            Failure\Kind::closedByServer,
            $connection->wait(Method::channelOpenOk)->match(
                static fn() => null,
                static fn($failure) => $failure->kind(),
            ),
        );
    }
}
