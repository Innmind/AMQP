<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\Reject;
use PHPUnit\Framework\TestCase;

class RejectTest extends TestCase
{
    public function testInterface()
    {
        $command = Reject::of(42);

        $this->assertSame(42, $command->deliveryTag());
        $this->assertFalse($command->shouldRequeue());
    }

    public function testRequeue()
    {
        $command = Reject::requeue(42);

        $this->assertInstanceOf(Reject::class, $command);
        $this->assertSame(42, $command->deliveryTag());
        $this->assertTrue($command->shouldRequeue());
    }
}
