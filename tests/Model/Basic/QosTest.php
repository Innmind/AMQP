<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\{
    Model\Basic\Qos,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class QosTest extends TestCase
{
    public function testInterface()
    {
        $command = new Qos(0, 1);

        $this->assertSame(0, $command->prefetchSize());
        $this->assertSame(1, $command->prefetchCount());
        $this->assertFalse($command->isGlobal());
    }

    public function testGlobal()
    {
        $command = Qos::global(0, 1);

        $this->assertInstanceOf(Qos::class, $command);
        $this->assertSame(0, $command->prefetchSize());
        $this->assertSame(1, $command->prefetchCount());
        $this->assertTrue($command->isGlobal());
    }

    public function testThrowWhenNegativePrefetchSize()
    {
        $this->expectException(DomainException::class);

        new Qos(-1, 0);
    }

    public function testThrowWhenNegativePrefetchCount()
    {
        $this->expectException(DomainException::class);

        new Qos(0, -1);
    }
}
