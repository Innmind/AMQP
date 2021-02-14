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
            new UnsignedLongInteger(new Integer(0))
        );
    }

    public function testThrowWhenIntegerTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('4294967296 ∉ [0;4294967295]');

        UnsignedLongInteger::of(new Integer(4294967296));
    }

    public function testThrowWhenIntegerTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-1 ∉ [0;4294967295]');

        UnsignedLongInteger::of(new Integer(-1));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            (new UnsignedLongInteger(new Integer($int)))->pack()
        );
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = UnsignedLongInteger::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(UnsignedLongInteger::class, $value);
        $this->assertInstanceOf(Integer::class, $value->original());
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, $value->pack());
    }

    public function cases(): array
    {
        return [
            [0, \pack('N', 0)],
            [4294967295, \pack('N', 4294967295)],
            [42, \pack('N', 42)],
        ];
    }
}
