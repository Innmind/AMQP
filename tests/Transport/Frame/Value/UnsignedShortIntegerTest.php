<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedShortInteger,
    Value
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class UnsignedShortIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new UnsignedShortInteger(new Integer(0))
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = new UnsignedShortInteger($int = new Integer($int));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromString($expected, $string)
    {
        $value = UnsignedShortInteger::fromString(new Str($string));

        $this->assertInstanceOf(UnsignedShortInteger::class, $value);
        $this->assertInstanceOf(Integer::class, $value->original());
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($_, $string)
    {
        $str = UnsignedShortInteger::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage 65536 ∉ [0;65535]∩ℤ
     */
    public function testThrowWhenIntegerTooHigh()
    {
        new UnsignedShortInteger(new Integer(65536));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage -1 ∉ [0;65535]∩ℤ
     */
    public function testThrowWhenIntegerTooLow()
    {
        new UnsignedShortInteger(new Integer(-1));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\StringNotOfExpectedLength
     * @expectedExceptionMessage String "foo" is expected of being 2 characters, got 3
     */
    public function testThrowWhenStringNotOfExpectedLength()
    {
        UnsignedShortInteger::fromString(new Str('foo'));
    }

    public function cases(): array
    {
        return [
            [0, pack('n', 0)],
            [42, pack('n', 42)],
            [32767, pack('n', 32767)],
            [65535, pack('n', 65535)],
        ];
    }
}
