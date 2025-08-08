<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\SignedLongInteger,
    Transport\Frame\Value,
};
use Innmind\Math\Exception\OutOfDefinitionSet;
use Innmind\IO\IO;
use Innmind\Immutable\Str;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{
    DataProvider,
    Group,
};

class SignedLongIntegerTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            SignedLongInteger::of(0),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    #[DataProvider('cases')]
    public function testStringCast($int, $expected)
    {
        $value = SignedLongInteger::of($int);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($int, $value->original());
    }

    #[Group('ci')]
    #[Group('local')]
    #[DataProvider('cases')]
    public function testFromStream($expected, $string)
    {
        $tmp = \fopen('php://temp', 'w+');
        \fwrite($tmp, $string);
        \fseek($tmp, 0);

        $value = IO::fromAmbientAuthority()
            ->streams()
            ->acquire($tmp)
            ->read()
            ->toEncoding(Str\Encoding::ascii)
            ->frames(SignedLongInteger::frame())
            ->one()
            ->match(
                static fn($value) => $value->unwrap(),
                static fn() => null,
            );

        $this->assertInstanceOf(SignedLongInteger::class, $value);
        $this->assertSame($expected, $value->original());
        $this->assertSame($string, $value->pack()->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testThrowWhenIntegerTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('2147483648 ∉ [-2147483648;2147483647]');

        SignedLongInteger::of(2147483648);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testThrowWhenIntegerTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-2147483649 ∉ [-2147483648;2147483647]');

        SignedLongInteger::of(-2147483649);
    }

    public static function cases(): array
    {
        return [
            [0, \pack('l', 0)],
            [-2147483648, \pack('l', -2147483648)],
            [2147483647, \pack('l', 2147483647)],
            [42, \pack('l', 42)],
        ];
    }
}
