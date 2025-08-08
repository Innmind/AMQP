<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\SignedOctet,
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

class SignedOctetTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, SignedOctet::of(0));
    }

    #[Group('ci')]
    #[Group('local')]
    #[DataProvider('cases')]
    public function testStringCast($expected, $octet)
    {
        $value = SignedOctet::of($octet);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($octet, $value->original());
    }

    #[Group('ci')]
    #[Group('local')]
    #[DataProvider('cases')]
    public function testFromStream($string, $expected)
    {
        $tmp = \fopen('php://temp', 'w+');
        \fwrite($tmp, $string);
        \fseek($tmp, 0);

        $value = IO::fromAmbientAuthority()
            ->streams()
            ->acquire($tmp)
            ->read()
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

    #[Group('ci')]
    #[Group('local')]
    public function testThrowWhenStringTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('128 ∉ [-128;127]');

        SignedOctet::of(128);
    }

    #[Group('ci')]
    #[Group('local')]
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
