<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\{
    Model\Connection\MaxFrameSize,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class MaxFrameSizeTest extends TestCase
{
    public function testInterface()
    {
        $max = new MaxFrameSize(42);

        $this->assertSame(42, $max->toInt());
        $this->assertTrue($max->allows(0));
        $this->assertTrue($max->allows(42));
        $this->assertFalse($max->allows(43));

        $this->assertTrue((new MaxFrameSize(0))->allows(1));
    }

    public function testIsLimited()
    {
        $this->assertTrue((new MaxFrameSize(42))->isLimited());
        $this->assertTrue((new MaxFrameSize(9))->isLimited());
        $this->assertFalse((new MaxFrameSize(0))->isLimited());
    }

    public function testThrowWhenNegativeValue()
    {
        $this->expectException(DomainException::class);

        new MaxFrameSize(-1);
    }

    /**
     * @dataProvider invalid
     */
    public function testThrowWhenValueLowerThanFrameFlags($int)
    {
        $this->expectException(DomainException::class);

        //meaning that channel id + payload size int + frame end flag
        //already make a size of 8 leaving no place for the payload
        new MaxFrameSize($int);
    }

    public function invalid(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
        ];
    }
}
