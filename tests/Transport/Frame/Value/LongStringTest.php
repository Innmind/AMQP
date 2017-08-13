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
        $this->assertSame(
            $expected,
            (string) new LongString(new Str($string))
        );
    }

    public function cases(): array
    {
        return [
            ['', pack('N', 0)],
            ['foo🙏bar', pack('N', 10).'foo🙏bar'],
        ];
    }
}
