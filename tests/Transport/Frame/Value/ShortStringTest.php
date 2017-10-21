<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\ShortString,
    Value
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class ShortStringTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new ShortString(new Str('')));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $value = new ShortString($str = new Str($string));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($str, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromString($expected, $string)
    {
        $value = ShortString::fromString(new Str($string));

        $this->assertInstanceOf(ShortString::class, $value);
        $this->assertSame($expected, (string) $value->original());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($_, $string)
    {
        $str = ShortString::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage 256 ∉ [0;255]
     */
    public function testThrowWhenStringTooLong()
    {
        new ShortString(new Str(str_repeat('i', 256)));
    }

    public function cases(): array
    {
        return [
            ['', chr(0)],
            ['foo🙏bar', chr(10).'foo🙏bar'],
        ];
    }
}
