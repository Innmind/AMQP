<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Connection\Connection,
    Transport\Connection as ConnectionInterface,
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
use Innmind\Stream\Readable;
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
        $connection = new Connection(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            new Remote\Generic($this->createMock(Server::class)),
            new Sockets\Unix,
        );

        $this->assertInstanceOf(ConnectionInterface::class, $connection);
        $this->assertSame($protocol, $connection->protocol());
        $this->assertInstanceOf(MaxFrameSize::class, $connection->maxFrameSize());
        $this->assertSame(131072, $connection->maxFrameSize()->toInt());
        $this->assertNull(
            $connection->send(
                $protocol->channel()->open(new Channel(1))
            )
        );
        $this->assertInstanceOf(Frame::class, $connection->wait('channel.open-ok'));
        $connection->close(); //test it closes without exception
    }

    public function testClose()
    {
        $connection = new Connection(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            new Remote\Generic($this->createMock(Server::class)),
            new Sockets\Unix,
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
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            new Remote\Generic($this->createMock(Server::class)),
            new Sockets\Unix,
        );

        $this->expectException(UnexpectedFrame::class);

        $connection->send($protocol->channel()->open(new Channel(2)));
        $connection->wait('connection.open');
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

            public function use(Version $version): void
            {
                if (!$version->compatibleWith($this->version)) {
                    throw new VersionNotUsable($version);
                }
            }

            public function read(Method $method, Readable $arguments): Sequence
            {
            }
            public function readHeader(Readable $arguments): Sequence
            {
            }
            public function method(string $name): Method
            {
            }
            public function connection(): PConnection
            {
            }
            public function channel(): PChannel
            {
            }
            public function exchange(): Exchange
            {
            }
            public function queue(): Queue
            {
            }
            public function basic(): Basic
            {
            }
            public function transaction(): Transaction
            {
            }
        };
        $connection = new Connection(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Delegate($top, new Protocol($this->createMock(ArgumentTranslator::class))),
            new ElapsedPeriod(1000),
            new Clock,
            new Remote\Generic($this->createMock(Server::class)),
            new Sockets\Unix,
        );

        $this->assertSame(
            "AMQP\x00\x00\x09\x01",
            $connection->protocol()->version()->toString(),
        );
        $this->assertNull(
            $connection->send(
                $protocol->channel()->open(new Channel(1))
            )
        );
        $this->assertInstanceOf(Frame::class, $connection->wait('channel.open-ok'));
        unset($connection); //test it closes without exception
    }

    public function testThrowWhenConnectionClosedByServer()
    {
        $connection = new Connection(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5672/'),
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            new Remote\Generic($this->createMock(Server::class)),
            new Sockets\Unix,
        );

        try {
            $connection->send(Frame::method(
                new Channel(0),
                new Method(20, 10)
                //missing arguments
            ));
            $connection->wait('channel.open-ok');
        } catch (ConnectionClosed $e) {
            $this->assertTrue($connection->closed());
            $this->assertSame('INTERNAL_ERROR', $e->getMessage());
            $this->assertSame(541, $e->getCode());
            $this->assertTrue($e->cause()->equals(new Method(0, 0)));
        }
    }
}
