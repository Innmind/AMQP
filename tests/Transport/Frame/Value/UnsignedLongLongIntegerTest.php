<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedLongLongInteger,
    Value
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\Str;
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

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage -1 ∉ [0;+∞]
     */
    public function testThrowWhenIntegerTooLow()
    {
        new UnsignedLongLongInteger(new Integer(-1));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = new UnsignedLongLongInteger($int = new Integer($int));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromString($expected, $string)
    {
        $value = UnsignedLongLongInteger::fromString(new Str($string));

        $this->assertInstanceOf(UnsignedLongLongInteger::class, $value);
        $this->assertInstanceOf(Integer::class, $value->original());
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($_, $string)
    {
        $str = UnsignedLongLongInteger::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\StringNotOfExpectedLength
     * @expectedExceptionMessage String "foo" is expected of being 8 characters, got 3
     */
    public function testThrowWhenStringNotOfExpectedLength()
    {
        UnsignedLongLongInteger::fromString(new Str('foo'));
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
