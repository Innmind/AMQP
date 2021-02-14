<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value,
};
use Innmind\Math\{
    Algebra\Integer,
    Exception\OutOfDefinitionSet,
};
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class UnsignedLongLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new UnsignedLongLongInteger(new Integer(0))
        );
    }

    public function testThrowWhenIntegerTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-1 ∉ [0;+∞]');

        UnsignedLongLongInteger::of(new Integer(-1));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = new UnsignedLongLongInteger($int = new Integer($int));
        $this->assertSame($expected, $value->pack());
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = UnsignedLongLongInteger::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(UnsignedLongLongInteger::class, $value);
        $this->assertInstanceOf(Integer::class, $value->original());
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, $value->pack());
    }

    public function cases(): array
    {
        return [
            [0, \pack('J', 0)],
            [4294967295, \pack('J', 4294967295)],
            [4294967296, \pack('J', 4294967296)],
            [\PHP_INT_MAX, \pack('J', \PHP_INT_MAX)],
            [42, \pack('J', 42)],
        ];
    }
}
