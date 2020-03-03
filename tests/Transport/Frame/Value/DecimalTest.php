<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\Decimal,
    Transport\Frame\Value,
    Exception\OutOfRangeValue,
};
use Innmind\Math\Algebra\{
    Number,
    Integer,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\StreamInterface;
use PHPUnit\Framework\TestCase;

class DecimalTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new Decimal(new Integer(100), new Integer(2))
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($number, $scale, $expected)
    {
        $value = new Decimal(new Integer($number), new Integer($scale));
        $this->assertSame($expected, $value->pack());
        $this->assertInstanceOf(Number::class, $value->original());
        $this->assertSame("$number ÷ (10^$scale)", $value->original()->toString());
        $this->assertSame($number / (10**$scale), $value->original()->value());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($number, $scale, $string)
    {
        $value = Decimal::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(Decimal::class, $value);
        $this->assertSame(($number / (10**$scale)), $value->original()->value());
        $this->assertSame($string, $value->pack());
    }

    public function testThrowWhenValueTooHigh()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('2147483648 ∉ [-2147483648;2147483647]');

        Decimal::of(new Integer(2147483648), new Integer(0));
    }

    public function testThrowWhenValueTooLow()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('-2147483649 ∉ [-2147483648;2147483647]');

        Decimal::of(new Integer(-2147483649), new Integer(0));
    }

    public function testThrowWhenScaleTooHigh()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('256 ∉ [0;255]');

        Decimal::of(new Integer(1), new Integer(256));
    }

    public function testThrowWhenScaleTooLow()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('-1 ∉ [0;255]');

        Decimal::of(new Integer(1), new Integer(-1));
    }

    public function cases(): array
    {
        return [
            [100, 2, chr(2).pack('l', 100)],
            [100, 0, chr(0).pack('l', 100)],
        ];
    }
}
