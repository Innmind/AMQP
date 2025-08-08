<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\UnsignedOctet,
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

class UnsignedOctetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            UnsignedOctet::of(0),
        );
    }

    #[DataProvider('cases')]
    public function testStringCast($expected, $octet)
    {
        $value = UnsignedOctet::of($octet);
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
            ->frames(UnsignedOctet::frame())
            ->one()
            ->match(
                static fn($value) => $value->unwrap(),
                static fn() => null,
            );

        $this->assertInstanceOf(UnsignedOctet::class, $value);
        $this->assertSame($expected, $value->original());
        $this->assertSame($string, $value->pack()->toString());
    }

    public function testThrowWhenStringTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('256 ∉ [0;255]');

        UnsignedOctet::of(256);
    }

    public function testThrowWhenStringTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-1 ∉ [0;255]');

        UnsignedOctet::of(-1);
    }

    public static function cases(): array
    {
        return [
            [\chr(0), 0],
            [\chr(127), 127],
            [\chr(255), 255],
        ];
    }
}
