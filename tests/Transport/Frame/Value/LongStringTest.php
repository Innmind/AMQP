<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\LongString,
    Value,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class LongStringTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new LongString(Str::of('')));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $value = new LongString($str = Str::of($string));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($str, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = LongString::fromStream(Stream::ofContent($string));

        $this->assertInstanceOf(LongString::class, $value);
        $this->assertInstanceOf(Str::class, $value->original());
        $this->assertSame($expected, $value->original()->toString());
        $this->assertSame($string, (string) $value);
    }

    public function cases(): array
    {
        return [
            ['', pack('N', 0)],
            ['foo🙏bar', pack('N', 10).'foo🙏bar'],
        ];
    }
}
