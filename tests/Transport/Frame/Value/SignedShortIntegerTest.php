<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedShortInteger,
    Value
};
use PHPUnit\Framework\TestCase;

class SignedShortIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new SignedShortInteger(0));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new SignedShortInteger($int)
        );
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between -32768 and 32767, got 32768
     */
    public function testThrowWhenIntegerTooHigh()
    {
        new SignedShortInteger(32768);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between -32768 and 32767, got -32769
     */
    public function testThrowWhenIntegerTooLow()
    {
        new SignedShortInteger(-32769);
    }

    public function cases(): array
    {
        return [
            [0, pack('s', 0)],
            [-32768, pack('s', -32768)],
            [32767, pack('s', 32767)],
            [42, pack('s', 42)],
        ];
    }
}
