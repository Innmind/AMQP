<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Bits,
    Value
};
use PHPUnit\Framework\TestCase;

class BitsTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new Bits);
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($bits, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new Bits(...$bits)
        );
    }

    public function cases(): array
    {
        return [
            [[], "\x00"],
            [[false], "\x00"],
            [[true], "\x01"],
            [[false, false], "\x00"],
            [[false, true], "\x02"],
            [[true, false], "\x01"],
            [[true, true], "\x03"],
        ];
    }
}
