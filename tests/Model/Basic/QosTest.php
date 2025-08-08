<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\Qos;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class QosTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $command = Qos::of(0, 1);

        $this->assertSame(0, $command->prefetchSize());
        $this->assertSame(1, $command->prefetchCount());
        $this->assertFalse($command->isGlobal());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testGlobal()
    {
        $command = Qos::global(0, 1);

        $this->assertInstanceOf(Qos::class, $command);
        $this->assertSame(0, $command->prefetchSize());
        $this->assertSame(1, $command->prefetchCount());
        $this->assertTrue($command->isGlobal());
    }
}
