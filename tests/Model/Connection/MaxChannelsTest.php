<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\MaxChannels;
use PHPUnit\Framework\TestCase;

class MaxChannelsTest extends TestCase
{
    public function testInterface()
    {
        $max = new MaxChannels(42);

        $this->assertSame(42, $max->toInt());
        $this->assertTrue($max->allows(0));
        $this->assertTrue($max->allows(42));
        $this->assertFalse($max->allows(43));

        $this->assertTrue((new MaxChannels(0))->allows(1));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\DomainException
     */
    public function testThrowWhenNegativeValue()
    {
        new MaxChannels(-1);
    }
}
