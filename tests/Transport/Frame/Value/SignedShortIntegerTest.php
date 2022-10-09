<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\SignedShortInteger,
    Transport\Frame\Value,
};
use Innmind\Math\Exception\OutOfDefinitionSet;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class SignedShortIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            SignedShortInteger::of(0),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = SignedShortInteger::of($int);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = SignedShortInteger::unpack(Stream::ofContent($string))->match(
            static fn($value) => $value,
            static fn() => null,
        );

        $this->assertInstanceOf(SignedShortInteger::class, $value);
        $this->assertSame($expected, $value->original());
        $this->assertSame($string, $value->pack()->toString());
    }

    public function testThrowWhenIntegerTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('32768 ∉ [-32768;32767]');

        SignedShortInteger::of(32768);
    }

    public function testThrowWhenIntegerTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-32769 ∉ [-32768;32767]');

        SignedShortInteger::of(-32769);
    }

    public function cases(): array
    {
        return [
            [0, \pack('s', 0)],
            [-32768, \pack('s', -32768)],
            [32767, \pack('s', 32767)],
            [42, \pack('s', 42)],
        ];
    }
}
