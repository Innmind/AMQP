<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedLongInteger,
    Value
};
use PHPUnit\Framework\TestCase;

class SignedLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new SignedLongInteger(0));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new SignedLongInteger($int)
        );
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between -2147483648 and 2147483647, got 2147483648
     */
    public function testThrowWhenIntegerTooHigh()
    {
        new SignedLongInteger(2147483648);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between -2147483648 and 2147483647, got -2147483649
     */
    public function testThrowWhenIntegerTooLow()
    {
        new SignedLongInteger(-2147483649);
    }

    public function cases(): array
    {
        return [
            [0, pack('l', 0)],
            [-2147483648, pack('l', -2147483648)],
            [2147483647, pack('l', 2147483647)],
            [42, pack('l', 42)],
        ];
    }
}
