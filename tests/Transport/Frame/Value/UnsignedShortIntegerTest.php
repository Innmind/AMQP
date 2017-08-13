<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedShortInteger,
    Value
};
use PHPUnit\Framework\TestCase;

class UnsignedShortIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new UnsignedShortInteger(0));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new UnsignedShortInteger($int)
        );
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between 0 and 65535, got 65536
     */
    public function testThrowWhenIntegerTooHigh()
    {
        new UnsignedShortInteger(65536);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between 0 and 65535, got -1
     */
    public function testThrowWhenIntegerTooLow()
    {
        new UnsignedShortInteger(-1);
    }

    public function cases(): array
    {
        return [
            [0, pack('n', 0)],
            [42, pack('n', 42)],
            [32767, pack('n', 32767)],
            [65535, pack('n', 65535)],
        ];
    }
}
