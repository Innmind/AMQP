<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Protocol\v091\Protocol,
    Transport\Protocol as ProtocolInterface,
    Transport\Protocol\Version,
    Transport\Protocol\Connection as PConnection,
    Transport\Protocol\Channel as PChannel,
    Transport\Protocol\Exchange,
    Transport\Protocol\Queue,
    Transport\Protocol\Basic,
    Transport\Protocol\Transaction,
    Transport\Protocol\Delegate,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Exception\VersionNotUsable
};
use Innmind\Socket\Internet\Transport;
use Innmind\Url\Url;
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Immutable\{
    Str,
    StreamInterface
};
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testInterface()
    {
        $connection = new Connection(
            Transport::tcp(),
            Url::fromString('//guest:guest@localhost:5672/'),
            $protocol = new Protocol,
            new ElapsedPeriod(1000)
        );

        $this->assertSame($protocol, $connection->protocol());
        $this->assertSame(
            $connection,
            $connection->send(
                $protocol->channel()->open(new Channel(1))
            )
        );
        $this->assertInstanceOf(Frame::class, $connection->wait('channel.open-ok'));
        unset($connection); //test it closes without exception
    }

    public function testClose()
    {
        $connection = new Connection(
            Transport::tcp(),
            Url::fromString('//guest:guest@localhost:5672/'),
            $protocol = new Protocol,
            new ElapsedPeriod(1000)
        );

        $this->assertTrue($connection->opened());
        $this->assertNull($connection->close());
        $this->assertFalse($connection->opened());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\UnexpectedFrame
     */
    public function testThrowWhenReceivedFrameIsNotTheExpectedOne()
    {
        $connection = new Connection(
            Transport::tcp(),
            Url::fromString('//guest:guest@localhost:5672/'),
            $protocol = new Protocol,
            new ElapsedPeriod(1000)
        );
        $connection
            ->send(
                $protocol->channel()->open(new Channel(2))
            )
            ->wait('connection.open');
    }

    public function testUseCorrectProtocolVersion()
    {
        $top = new class implements ProtocolInterface {
            private $version;

            public function __construct()
            {
                $this->version = new Version(1, 0, 0);
            }

            public function version(): Version
            {
                return $this->version;
            }

            public function use(Version $version): ProtocolInterface
            {
                if (!$version->compatibleWith($this->version)) {
                    throw new VersionNotUsable($version);
                }

                return $this;
            }

            public function read(Method $method, Str $arguments): StreamInterface {}
            public function method(string $name): Method {}
            public function connection(): PConnection {}
            public function channel(): PChannel {}
            public function exchange(): Exchange {}
            public function queue(): Queue {}
            public function basic(): Basic {}
            public function transaction(): Transaction {}
        };
        $connection = new Connection(
            Transport::tcp(),
            Url::fromString('//guest:guest@localhost:5672/'),
            $protocol = new Delegate($top, new Protocol),
            new ElapsedPeriod(1000)
        );

        $this->assertSame(
            "AMQP\x00\x00\x09\x01",
            (string) $connection->protocol()->version()
        );
        $this->assertSame(
            $connection,
            $connection->send(
                $protocol->channel()->open(new Channel(1))
            )
        );
        $this->assertInstanceOf(Frame::class, $connection->wait('channel.open-ok'));
        unset($connection); //test it closes without exception
    }
}
