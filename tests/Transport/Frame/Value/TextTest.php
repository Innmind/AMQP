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
        $this->assertSame(
            $expected,
            (string) new Text(new Str($string))
        );
    }

    public function cases(): array
    {
        return [
            ['', ''],
            ['foo🙏bar', 'foo🙏bar'],
        ];
    }
}
