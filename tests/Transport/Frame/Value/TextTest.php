<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Text,
    Value
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new Text(new Str('')));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $value = new Text($str = new Str($string));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($str, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromString($expected, $string)
    {
        $value = Text::fromString($str = new Str($string));

        $this->assertInstanceOf(Text::class, $value);
        $this->assertSame($str, $value->original());
        $this->assertSame($expected, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($_, $string)
    {
        $value = Text::cut($str = new Str($string));

        $this->assertSame($str, $value);
    }

    public function cases(): array
    {
        return [
            ['', ''],
            ['foo🙏bar', 'foo🙏bar'],
        ];
    }
}
