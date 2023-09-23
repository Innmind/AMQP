<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value,
};
use Innmind\Math\{
    Algebra\Integer,
    Exception\OutOfDefinitionSet,
};
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class UnsignedLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            UnsignedLongInteger::of(0),
        );
    }

    public function testThrowWhenIntegerTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('4294967296 ∉ [0;4294967295]');

        UnsignedLongInteger::of(4294967296);
    }

    public function testThrowWhenIntegerTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-1 ∉ [0;4294967295]');

        UnsignedLongInteger::of(-1);
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            UnsignedLongInteger::of($int)->pack()->toString(),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = UnsignedLongInteger::unpack(Stream::ofContent($string))->match(
            static fn($value) => $value,
            static fn() => null,
        );

        $this->assertInstanceOf(UnsignedLongInteger::class, $value);
        $this->assertSame($expected, $value->original());
        $this->assertSame($string, $value->pack()->toString());
    }

    public static function cases(): array
    {
        return [
            [0, \pack('N', 0)],
            [4294967295, \pack('N', 4294967295)],
            [42, \pack('N', 42)],
        ];
    }
}
