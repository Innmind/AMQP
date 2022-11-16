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
use Innmind\Immutable\Str;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $basic = new Logger(
            $this->createMock(Basic::class),
            $this->createMock(LoggerInterface::class),
        );

        $this->assertInstanceOf(Basic::class, $basic);
        $this->assertNull($basic->ack(Ack::of(1)));
        $this->assertNull($basic->cancel(Cancel::of('')));
        $this->assertInstanceOf(
            Basic\Consumer\Logger::class,
            $basic->consume(Consume::of('')),
        );
        $this->assertInstanceOf(
            Basic\Get\Logger::class,
            $basic->get(Get::of('')),
        );
        $this->assertNull($basic->publish(Publish::a(Message::of(Str::of('')))));
        $this->assertNull($basic->qos(Qos::of(0, 0)));
        $this->assertNull($basic->recover(Recover::withoutRequeue()));
        $this->assertNull($basic->reject(Reject::of(1)));
    }
}
