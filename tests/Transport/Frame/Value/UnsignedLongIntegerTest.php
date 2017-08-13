<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedLongInteger,
    Value
};
use PHPUnit\Framework\TestCase;

class UnsignedLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new UnsignedLongInteger(0));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between 0 and 4294967295, got 4294967296
     */
    public function testThrowWhenIntegerTooHigh()
    {
        new UnsignedLongInteger(4294967296);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between 0 and 4294967295, got -1
     */
    public function testThrowWhenIntegerTooLow()
    {
        new UnsignedLongInteger(-1);
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new UnsignedLongInteger($int)
        );
    }

    public function cases(): array
    {
        return [
            [0, pack('N', 0)],
            [4294967295, pack('N', 4294967295)],
            [42, pack('N', 42)],
        ];
    }
}
