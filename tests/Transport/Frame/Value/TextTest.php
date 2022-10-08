<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Text,
    Value,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, Text::of(Str::of('')));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $value = Text::of($str = Str::of($string));
        $this->assertSame($expected, $value->pack());
        $this->assertSame($str, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = Text::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(Text::class, $value);
        $this->assertSame($string, $value->original()->toString());
        $this->assertSame($expected, $value->pack());
    }

    public function cases(): array
    {
        return [
            ['', ''],
            ['foo🙏bar', 'foo🙏bar'],
        ];
    }
}
