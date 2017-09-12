<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedLongInteger,
    Value
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\Str;
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

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage 4294967296 ∉ [0;4294967295]∩ℤ
     */
    public function testThrowWhenIntegerTooHigh()
    {
        new UnsignedLongInteger(new Integer(4294967296));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage -1 ∉ [0;4294967295]∩ℤ
     */
    public function testThrowWhenIntegerTooLow()
    {
        new UnsignedLongInteger(new Integer(-1));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new UnsignedLongInteger(new Integer($int))
        );
    }

    /**
     * @expectedException Innmind\AMQP\Exception\StringNotOfExpectedLength
     * @expectedExceptionMessage String "foo" is expected of being 4 characters, got 3
     */
    public function testThrowWhenStringNotOfAppropriateLength()
    {
        UnsignedLongInteger::fromString(new Str('foo'));
    }

    /**
     * @dataProvider cases
     */
    public function testFromString($expected, $string)
    {
        $value = UnsignedLongInteger::fromString(new Str($string));

        $this->assertInstanceOf(UnsignedLongInteger::class, $value);
        $this->assertInstanceOf(Integer::class, $value->original());
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($expected, $string)
    {
        $str = UnsignedLongInteger::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    public function cases(): array
    {
        return [
            [0, pack('N', 0)],
            [4294967295, pack('N', 4294967295)],
            [42, pack('N', 42)],
        ];
    }
}
