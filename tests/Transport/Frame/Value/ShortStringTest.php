<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value,
    Exception\OutOfRangeValue,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class ShortStringTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new ShortString(Str::of('')));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $value = new ShortString($str = Str::of($string));
        $this->assertSame($expected, $value->pack());
        $this->assertSame($str, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = ShortString::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(ShortString::class, $value);
        $this->assertSame($expected, $value->original()->toString());
        $this->assertSame($string, $value->pack());
    }

    public function testThrowWhenTooLongString()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('256 ∉ [0;255]');

        ShortString::of(Str::of(\str_repeat('a', 256)));
    }

    public function cases(): array
    {
        return [
            ['', chr(0)],
            ['foo🙏bar', chr(10).'foo🙏bar'],
        ];
    }
}
