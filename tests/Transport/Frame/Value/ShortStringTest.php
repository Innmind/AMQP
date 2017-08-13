<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\ShortString,
    Value
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class ShortStringTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new ShortString(new Str('')));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($string, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new ShortString(new Str($string))
        );
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between 0 and 255, got 256
     */
    public function testThrowWhenStringTooLong()
    {
        new ShortString(new Str(str_repeat('i', 256)));
    }

    public function cases(): array
    {
        return [
            ['', chr(0)],
            ['foo🙏bar', chr(10).'foo🙏bar'],
        ];
    }
}
