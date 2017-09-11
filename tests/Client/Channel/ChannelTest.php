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
    Transport\Frame\Channel as Number
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    TimeContinuum\Earth
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
    private $channel;
    private $connection;

    public function setUp()
    {
        $this->channel = new Channel(
            $this->connection = new Connection(
                Transport::tcp(),
                Url::fromString('//guest:guest@localhost:5672/'),
                new Protocol(new ValueTranslator),
                new ElapsedPeriod(1000),
                new Earth
            ),
            new Number(1)
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
