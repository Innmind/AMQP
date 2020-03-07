<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Basic;

use Innmind\AMQP\{
    Client\Channel\Basic\Logger,
    Client\Channel\Basic,
    Model\Basic\Ack,
    Model\Basic\Cancel,
    Model\Basic\Consume,
    Model\Basic\Get,
    Model\Basic\Publish,
    Model\Basic\Message,
    Model\Basic\Qos,
    Model\Basic\Recover,
    Model\Basic\Reject,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $basic = new Logger(
            $this->createMock(Basic::class),
            $this->createMock(LoggerInterface::class)
        );

        $this->assertInstanceOf(Basic::class, $basic);
        $this->assertNull($basic->ack(new Ack(1)));
        $this->assertNull($basic->cancel(new Cancel('')));
        $this->assertInstanceOf(
            Basic\Consumer\Logger::class,
            $basic->consume(new Consume(''))
        );
        $this->assertInstanceOf(
            Basic\Get\Logger::class,
            $basic->get(new Get(''))
        );
        $this->assertNull($basic->publish(new Publish($this->createMock(Message::class))));
        $this->assertNull($basic->qos(new Qos(0, 0)));
        $this->assertNull($basic->recover(new Recover));
        $this->assertNull($basic->reject(new Reject(1)));
    }
}
