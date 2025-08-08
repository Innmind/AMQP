<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\LongString,
    Value,
};
use Innmind\IO\IO;
use Innmind\Immutable\Str;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{
    DataProvider,
    Group,
};

class LongStringTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, LongString::literal(''));
    }

    #[Group('ci')]
    #[Group('local')]
    #[DataProvider('cases')]
    public function testStringCast($string, $expected)
    {
        $value = LongString::literal($string);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($string, $value->original()->toString());
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
            ->frames(LongString::frame())
            ->one()
            ->match(
                static fn($value) => $value->unwrap(),
                static fn() => null,
            );

        $this->assertInstanceOf(LongString::class, $value);
        $this->assertInstanceOf(Str::class, $value->original());
        $this->assertSame($expected, $value->original()->toString());
        $this->assertSame($string, $value->pack()->toString());
    }

    public static function cases(): array
    {
        return [
            ['', \pack('N', 0)],
            ['foo🙏bar', \pack('N', 10).'foo🙏bar'],
        ];
    }
}
