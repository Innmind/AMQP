<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\Decimal,
    Transport\Frame\Value,
};
use Innmind\Math\Exception\OutOfDefinitionSet;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class DecimalTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            Decimal::of(100, 2),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($number, $scale, $expected)
    {
        $value = Decimal::of($number, $scale);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($number / (10**$scale), $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($number, $scale, $string)
    {
        $value = Decimal::unpack(Stream::ofContent($string))->match(
            static fn($value) => $value,
            static fn() => null,
        );

        $this->assertInstanceOf(Decimal::class, $value);
        $this->assertSame(($number / (10**$scale)), $value->original());
        $this->assertSame($string, $value->pack()->toString());
    }

    public function testThrowWhenValueTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('2147483648 ∉ [-2147483648;2147483647]');

        Decimal::of(2147483648, 0);
    }

    public function testThrowWhenValueTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-2147483649 ∉ [-2147483648;2147483647]');

        Decimal::of(-2147483649, 0);
    }

    public function testThrowWhenScaleTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('256 ∉ [0;255]');

        Decimal::of(1, 256);
    }

    public function testThrowWhenScaleTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-1 ∉ [0;255]');

        Decimal::of(1, -1);
    }

    public function cases(): array
    {
        return [
            [100, 2, \chr(2).\pack('l', 100)],
            [100, 0, \chr(0).\pack('l', 100)],
        ];
    }
}
