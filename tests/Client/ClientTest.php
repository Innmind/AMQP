<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client\Client,
    Client as ClientInterface,
    Client\Channel,
    Transport\Connection,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Transport\Protocol,
};
use Innmind\Socket\Internet\Transport;
use Innmind\OperatingSystem\{
    CurrentProcess,
    Remote,
    Sockets,
};
use Innmind\Server\Control\{
    Server,
    Server\Process\Pid,
};
use Innmind\TimeContinuum\Earth\{
    ElapsedPeriod,
    Clock,
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private $client;
    private $connection;
    private $process;

    public function setUp(): void
    {
        $this->client = new Client(
            fn() => $this->connection = Connection::of(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5672/'),
                new Protocol(new Clock, new ValueTranslator),
                new ElapsedPeriod(1000),
                new Clock,
                Remote\Generic::of($this->createMock(Server::class), new Clock),
                Sockets\Unix::of(),
            )->match(
                static fn($connection) => $connection,
                static fn() => null,
            ),
            $this->process = $this->createMock(CurrentProcess::class),
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(ClientInterface::class, $this->client);
    }

    public function testChannel()
    {
        $this
            ->process
            ->expects($this->exactly(2))
            ->method('id')
            ->willReturn(new Pid(42));

        $channel = $this->client->channel();

        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame($channel, $this->client->channel()); //a single channel per php process
        $this->assertFalse($channel->closed());
    }

    public function testClose()
    {
        $this
            ->process
            ->expects($this->once())
            ->method('id')
            ->willReturn(new Pid(42));

        $this->client->channel();
        $this->assertFalse($this->client->closed());
        $this->assertNull($this->client->close());
        $this->assertTrue($this->client->closed());
        $this->assertTrue($this->connection->closed());
        $this->assertNull($this->client->close()); //ensure nothing happens
    }
}
