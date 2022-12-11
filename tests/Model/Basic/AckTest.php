<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\Ack;
use PHPUnit\Framework\TestCase;

class AckTest extends TestCase
{
    public function testInterface()
    {
        $command = Ack::of(42);

        $this->assertSame(42, $command->deliveryTag());
        $this->assertFalse($command->isMultiple());
    }

    public function testMultiple()
    {
        $command = Ack::multiple(42);

        $this->assertInstanceOf(Ack::class, $command);
        $this->assertSame(42, $command->deliveryTag());
        $this->assertTrue($command->isMultiple());
    }
}
