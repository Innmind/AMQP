<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport;

use Innmind\AMQP\Transport\{
    Connection,
    Protocol\v091\Protocol,
    Frame,
    Frame\Channel
};
use Innmind\Socket\Internet\Transport;
use Innmind\Url\Url;
use Innmind\TimeContinuum\ElapsedPeriod;
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
}
