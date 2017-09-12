<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\LongString,
    Value
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class LongStringTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new LongString(new Str('')));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $value = new LongString($str = new Str($string));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($str, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromString($expected, $string)
    {
        $value = LongString::fromString(new Str($string));

        $this->assertInstanceOf(LongString::class, $value);
        $this->assertInstanceOf(Str::class, $value->original());
        $this->assertSame($expected, (string) $value->original());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($_, $string)
    {
        $str = LongString::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\StringNotOfExpectedLength
     * @expectedExceptionMessage String "foo" is expected of being 0 characters, got 3
     */
    public function testThrowWhenLengthDoesntMatchString()
    {
        LongString::fromString(new Str(pack('N', 0).'foo'));
    }

    public function cases(): array
    {
        return [
            ['', pack('N', 0)],
            ['foo🙏bar', pack('N', 10).'foo🙏bar'],
        ];
    }
}
