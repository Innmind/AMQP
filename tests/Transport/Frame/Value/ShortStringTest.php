<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value,
    Exception\OutOfRangeValue,
};
use Innmind\Filesystem\Stream\StringStream;
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
    public function testFromStream($expected, $string)
    {
        $value = ShortString::fromStream(new StringStream($string));

        $this->assertInstanceOf(ShortString::class, $value);
        $this->assertSame($expected, (string) $value->original());
        $this->assertSame($string, (string) $value);
    }

    public function testThrowWhenTooLongString()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('256 ∉ [0;255]');

        ShortString::of(new Str(\str_repeat('a', 256)));
    }

    public function cases(): array
    {
        return [
            ['', chr(0)],
            ['foo🙏bar', chr(10).'foo🙏bar'],
        ];
    }
}
