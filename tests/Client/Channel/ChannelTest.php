<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel;

use Innmind\AMQP\{
    Client\Channel\Channel,
    Client\Channel as ChannelInterface,
    Client\Channel\Exchange,
    Client\Channel\Queue,
    Client\Channel\Basic,
    Client\Channel\Transaction,
    Transport\Connection\Connection,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Transport\Protocol\v091\Protocol,
    Transport\Frame\Channel as Number,
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

class ChannelTest extends TestCase
{
    private $channel;
    private $connection;

    public function setUp(): void
    {
        $this->channel = new Channel(
            $this->connection = new Connection(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5672/'),
                new Protocol(new ValueTranslator),
                new ElapsedPeriod(1000),
                new Clock,
                Remote\Generic::of($this->createMock(Server::class), new Clock),
                Sockets\Unix::of(),
            ),
            new Number(1),
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(ChannelInterface::class, $this->channel);
        $this->assertInstanceOf(Exchange::class, $this->channel->exchange());
        $this->assertInstanceOf(Queue::class, $this->channel->queue());
        $this->assertInstanceOf(Basic::class, $this->channel->basic());
        $this->assertInstanceOf(Transaction::class, $this->channel->transaction());
    }

    public function testClose()
    {
        $this->assertFalse($this->channel->closed());
        $this->assertNull($this->channel->close());
        $this->assertTrue($this->channel->closed());
        $this->assertNull($this->channel->close()); //ensure nothing happens
    }
}
