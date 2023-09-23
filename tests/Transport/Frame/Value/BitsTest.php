<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Bits,
    Value,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class BitsTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, Bits::of(true));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($bits, $expected)
    {
        $value = Bits::of(...$bits);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertInstanceOf(Sequence::class, $value->original());
        $this->assertSame($bits, $value->original()->toList());
    }

    /**
     * @dataProvider decode
     */
    public function testFromStream($expected, $string)
    {
        $value = Bits::unpack(Stream::ofContent($string))->match(
            static fn($value) => $value,
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
