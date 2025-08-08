<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value,
};
use Innmind\Math\Exception\OutOfDefinitionSet;
use Innmind\IO\IO;
use Innmind\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\Immutable\Str;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ShortStringTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, ShortString::literal(''));
    }

    #[DataProvider('cases')]
    public function testStringCast($string, $expected)
    {
        $value = ShortString::literal($string);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($string, $value->original()->toString());
    }

    #[DataProvider('cases')]
    public function testFromStream($expected, $string)
    {
        $value = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::ofContent($string))
            ->toEncoding(Str\Encoding::ascii)
            ->frames(ShortString::frame())
            ->one()
            ->match(
                static fn($value) => $value->unwrap(),
                static fn() => null,
            );

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

    public static function cases(): array
    {
        return [
            ['', \chr(0)],
            ['foo🙏bar', \chr(10).'foo🙏bar'],
        ];
    }
}
