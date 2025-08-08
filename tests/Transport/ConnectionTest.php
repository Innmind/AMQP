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
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class ConnectionTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $os = Factory::build();
        $connection = Connection::open(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol($os->clock(), new ArgumentTranslator),
            new ElapsedPeriod(1000),
            $os->clock(),
            $os->remote(),
            $os->sockets(),
        )->match(
            static fn($connection) => $connection,
            static fn() => null,
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $connection
                ->send(
                    static fn($protocol) => $protocol->channel()->open(new Channel(1)),
                )
                ->match(
                    static fn($sideEffect) => $sideEffect,
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

    #[Group('ci')]
    #[Group('local')]
    public function testClose()
    {
        $os = Factory::build();
        $connection = Connection::open(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol($os->clock(), new ArgumentTranslator),
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

    #[Group('ci')]
    #[Group('local')]
    public function testReturnFailureWhenReceivedFrameIsNotTheExpectedOne()
    {
        $os = Factory::build();
        $connection = Connection::open(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            new Protocol($os->clock(), new ArgumentTranslator),
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
                ->request(
                    static fn($protocol) => $protocol->channel()->open(new Channel(2)),
                    Method::connectionOpen,
                )
                ->match(
                    static fn() => null,
                    static fn($failure) => $failure->kind(),
                ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testReturnFailureWhenConnectionClosedByServer()
    {
        $os = Factory::build();
        $connection = Connection::open(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol($os->clock(), new ArgumentTranslator),
            new ElapsedPeriod(1000),
            $os->clock(),
            $os->remote(),
            $os->sockets(),
        )->match(
            static fn($connection) => $connection,
            static fn() => null,
        );

        $_ = $connection->send(static fn() => Sequence::of(Frame::method(
            new Channel(0),
            Method::of(20, 10),
            //missing arguments
        )))
            ->match(
                static fn() => null,
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
