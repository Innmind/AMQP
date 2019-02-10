<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Bits,
    Value
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    StreamInterface,
    Str
};
use PHPUnit\Framework\TestCase;

class BitsTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new Bits(true));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($bits, $expected)
    {
        $value = new Bits(...$bits);
        $this->assertSame($expected, (string) $value);
        $this->assertInstanceOf(StreamInterface::class, $value->original());
        $this->assertSame('bool', (string) $value->original()->type());
        $this->assertSame($bits, $value->original()->toPrimitive());
    }

    /**
     * @dataProvider decode
     */
    public function testFromString($expected, $string)
    {
        $value = Bits::fromString(new Str($string));

        $this->assertInstanceOf(Bits::class, $value);
        $this->assertSame($expected, $value->original()->toPrimitive());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider decode
     */
    public function testFromStream($expected, $string)
    {
        $value = Bits::fromStream(new StringStream($string));

        $this->assertInstanceOf(Bits::class, $value);
        $this->assertSame($expected, $value->original()->toPrimitive());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider decode
     */
    public function testCut($_, $string)
    {
        $str = Bits::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    public function cases(): array
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

    public function decode(): array
    {
        return [
            [[false], "\x00"],
            [[true], "\x01"],
            [[false, true], "\x02"],
            [[true, true], "\x03"],
        ];
    }
}
