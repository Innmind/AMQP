<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedLongLongInteger,
    Value
};
use PHPUnit\Framework\TestCase;

class UnsignedLongLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new UnsignedLongLongInteger(0));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between 0 and 9223372036854775807, got -1
     */
    public function testThrowWhenIntegerTooLow()
    {
        new UnsignedLongLongInteger(-1);
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new UnsignedLongLongInteger($int)
        );
    }

    public function cases(): array
    {
        return [
            [0, pack('J', 0)],
            [4294967295, pack('J', 4294967295)],
            [4294967296, pack('J', 4294967296)],
            [PHP_INT_MAX, pack('J', PHP_INT_MAX)],
            [42, pack('J', 42)],
        ];
    }
}
