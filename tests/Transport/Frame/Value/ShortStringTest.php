<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value,
};
use Innmind\Math\Exception\OutOfDefinitionSet;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class ShortStringTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, ShortString::literal(''));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $value = ShortString::literal($string);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($string, $value->original()->toString());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = ShortString::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(ShortString::class, $value);
        $this->assertSame($expected, $value->original()->toString());
        $this->assertSame($string, $value->pack()->toString());
    }

    public function testThrowWhenTooLongString()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('256 ∉ [0;255]');

        ShortString::of(Str::of(\str_repeat('a', 256)));
    }

    public function cases(): array
    {
        return [
            ['', \chr(0)],
            ['foo🙏bar', \chr(10).'foo🙏bar'],
        ];
    }
}
