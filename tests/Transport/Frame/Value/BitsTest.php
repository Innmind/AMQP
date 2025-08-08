<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Bits,
    Value,
};
use Innmind\IO\IO;
use Innmind\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class BitsTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, Bits::of(true));
    }

    #[DataProvider('cases')]
    public function testStringCast($bits, $expected)
    {
        $value = Bits::of(...$bits);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertInstanceOf(Sequence::class, $value->original());
        $this->assertSame($bits, $value->original()->toList());
    }

    #[DataProvider('decode')]
    public function testFromStream($expected, $string)
    {
        $value = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::ofContent($string))
            ->toEncoding(Str\Encoding::ascii)
            ->frames(Bits::frame())
            ->one()
            ->match(
                static fn($value) => $value->unwrap(),
                static fn() => null,
            );

        $this->assertInstanceOf(Bits::class, $value);
        $this->assertSame($expected, $value->original()->toList());
        $this->assertSame($string, $value->pack()->toString());
    }

    public static function cases(): array
    {
        return [
            [[false], "\x00"],
            [[true], "\x01"],
            [[false, false], "\x00"],
            [[false, true], "\x02"],
            [[true, false], "\x01"],
            [[true, true], "\x03"],
        ];
    }

    public static function decode(): array
    {
        return [
            [[false], "\x00"],
            [[true], "\x01"],
            [[false, true], "\x02"],
            [[true, true], "\x03"],
        ];
    }
}
