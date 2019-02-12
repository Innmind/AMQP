<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client\Client,
    Client as ClientInterface,
    Client\Channel,
    Transport\Connection\Connection,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Transport\Protocol\v091\Protocol,
};
use Innmind\Socket\Internet\Transport;
use Innmind\OperatingSystem\{
    CurrentProcess,
    Remote,
};
use Innmind\Server\Control\Server;
use Innmind\Server\Status\Server\Process\Pid;
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    TimeContinuum\Earth,
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
            $this->connection = new Connection(
                Transport::tcp(),
                Url::fromString('//guest:guest@localhost:5672/'),
                new Protocol(new ValueTranslator),
                new ElapsedPeriod(1000),
                new Earth,
                new Remote\Generic($this->createMock(Server::class))
            ),
            $this->process = $this->createMock(CurrentProcess::class)
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
