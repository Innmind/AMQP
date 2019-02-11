<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel;

use Innmind\AMQP\{
    Client\Channel\Basic\NullBasic,
    Client\Channel\Basic,
    Client\Channel\Basic\Get\GetEmpty,
    Client\Channel\Basic\Consumer\NullConsumer,
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
use PHPUnit\Framework\TestCase;

class NullBasicTest extends TestCase
{
    public function testInterface()
    {
        $basic = new NullBasic;

        $this->assertInstanceOf(Basic::class, $basic);
        $this->assertSame($basic, $basic->ack(new Ack(1)));
        $this->assertSame($basic, $basic->cancel(new Cancel('')));
        $this->assertInstanceOf(NullConsumer::class, $basic->consume(new Consume('')));
        $this->assertInstanceOf(GetEmpty::class, $basic->get(new Get('')));
        $this->assertSame($basic, $basic->publish(new Publish($this->createMock(Message::class))));
        $this->assertSame($basic, $basic->qos(new Qos(0, 0)));
        $this->assertSame($basic, $basic->recover(new Recover));
        $this->assertSame($basic, $basic->reject(new Reject(1)));
    }
}
