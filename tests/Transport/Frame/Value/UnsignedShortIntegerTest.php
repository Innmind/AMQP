<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value,
};
use Innmind\Math\Exception\OutOfDefinitionSet;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class UnsignedShortIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            UnsignedShortInteger::of(0),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = UnsignedShortInteger::of($int);
        $this->assertSame($expected, $value->pack());
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = UnsignedShortInteger::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(UnsignedShortInteger::class, $value);
        $this->assertSame($expected, $value->original());
        $this->assertSame($string, $value->pack());
    }

    public function testThrowWhenIntegerTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('65536 ∉ [0;65535]');

        UnsignedShortInteger::of(65536);
    }

    public function testThrowWhenIntegerTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-1 ∉ [0;65535]');

        UnsignedShortInteger::of(-1);
    }

    public function cases(): array
    {
        return [
            [0, \pack('n', 0)],
            [42, \pack('n', 42)],
            [32767, \pack('n', 32767)],
            [65535, \pack('n', 65535)],
        ];
    }
}
