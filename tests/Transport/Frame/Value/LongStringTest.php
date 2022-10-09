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
        $this->assertInstanceOf(Value::class, LongString::literal(''));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $value = LongString::literal($string);
        $this->assertSame($expected, $value->pack());
        $this->assertSame($string, $value->original()->toString());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = LongString::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(LongString::class, $value);
        $this->assertInstanceOf(Str::class, $value->original());
        $this->assertSame($expected, $value->original()->toString());
        $this->assertSame($string, $value->pack());
    }

    public function cases(): array
    {
        return [
            ['', \pack('N', 0)],
            ['foo🙏bar', \pack('N', 10).'foo🙏bar'],
        ];
    }
}
