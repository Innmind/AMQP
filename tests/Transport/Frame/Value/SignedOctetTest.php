<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\SignedOctet,
    Transport\Frame\Value,
};
use Innmind\Math\Exception\OutOfDefinitionSet;
use Innmind\IO\IO;
use Innmind\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\Immutable\Str;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SignedOctetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, SignedOctet::of(0));
    }

    #[DataProvider('cases')]
    public function testStringCast($expected, $octet)
    {
        $value = SignedOctet::of($octet);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($octet, $value->original());
    }

    #[DataProvider('cases')]
    public function testFromStream($string, $expected)
    {
        $value = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::ofContent($string))
            ->toEncoding(Str\Encoding::ascii)
            ->frames(SignedOctet::frame())
            ->one()
            ->match(
                static fn($value) => $value->unwrap(),
                static fn() => null,
            );

        $this->assertInstanceOf(SignedOctet::class, $value);
        $this->assertSame($expected, $value->original());
        $this->assertSame($string, $value->pack()->toString());
    }

    public function testThrowWhenStringTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('128 ∉ [-128;127]');

        SignedOctet::of(128);
    }

    public function testThrowWhenStringTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-129 ∉ [-128;127]');

        SignedOctet::of(-129);
    }

    public static function cases(): array
    {
        return [
            [\pack('c', 0), 0],
            [\pack('c', 127), 127],
            [\pack('c', -128), -128],
        ];
    }
}
