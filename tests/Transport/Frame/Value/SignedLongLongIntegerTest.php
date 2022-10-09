<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedLongLongInteger,
    Value,
};
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class SignedLongLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            SignedLongLongInteger::of(0),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = SignedLongLongInteger::of($int);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = SignedLongLongInteger::unpack(Stream::ofContent($string))->match(
            static fn($value) => $value,
            static fn() => null,
        );

        $this->assertInstanceOf(SignedLongLongInteger::class, $value);
        $this->assertSame($expected, $value->original());
        $this->assertSame($string, $value->pack()->toString());
    }

    public function cases(): array
    {
        return [
            [0, \pack('q', 0)],
            [-2147483648, \pack('q', -2147483648)],
            [-2147483649, \pack('q', -2147483649)],
            [2147483647, \pack('q', 2147483647)],
            [2147483648, \pack('q', 2147483648)],
            [42, \pack('q', 42)],
        ];
    }
}
