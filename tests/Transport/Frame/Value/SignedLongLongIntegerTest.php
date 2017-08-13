<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedLongLongInteger,
    Value
};
use PHPUnit\Framework\TestCase;

class SignedLongLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new SignedLongLongInteger(0));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new SignedLongLongInteger($int)
        );
    }

    public function cases(): array
    {
        return [
            [0, pack('q', 0)],
            [-2147483648, pack('q', -2147483648)],
            [-2147483649, pack('q', -2147483649)],
            [2147483647, pack('q', 2147483647)],
            [2147483648, pack('q', 2147483648)],
            [42, pack('q', 42)],
        ];
    }
}
