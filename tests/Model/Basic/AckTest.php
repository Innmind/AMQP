<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\Ack;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class AckTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $command = Ack::of(42);

        $this->assertSame(42, $command->deliveryTag());
        $this->assertFalse($command->isMultiple());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testMultiple()
    {
        $command = Ack::multiple(42);

        $this->assertInstanceOf(Ack::class, $command);
        $this->assertSame(42, $command->deliveryTag());
        $this->assertTrue($command->isMultiple());
    }
}
