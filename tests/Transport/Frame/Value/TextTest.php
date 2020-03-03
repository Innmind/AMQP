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
        $this->assertInstanceOf(Value::class, new Text(Str::of('')));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $value = new Text($str = Str::of($string));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($str, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = Text::fromStream(Stream::ofContent($string));

        $this->assertInstanceOf(Text::class, $value);
        $this->assertSame($string, $value->original()->toString());
        $this->assertSame($expected, (string) $value);
    }

    public function cases(): array
    {
        return [
            ['', ''],
            ['foo🙏bar', 'foo🙏bar'],
        ];
    }
}
