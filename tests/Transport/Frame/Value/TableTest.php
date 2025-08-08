<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\Table,
    Transport\Frame\Value\SignedOctet,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value,
};
use Innmind\TimeContinuum\Clock;
use Innmind\IO\IO;
use Innmind\Immutable\{
    Map,
    Str,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{
    DataProvider,
    Group,
};

class TableTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            Table::of(Map::of()),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    #[DataProvider('cases')]
    public function testStringCast($expected, $map)
    {
        $value = Table::of($map);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($map, $value->original());
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
            ->frames(Table::frame(Clock::live()))
            ->one()
            ->match(
                static fn($value) => $value->unwrap(),
                static fn() => null,
            );

        $this->assertInstanceOf(Table::class, $value);
        $this->assertCount($expected->size(), $value->original());

        foreach ($expected as $i => $v) {
            $this->assertInstanceOf(
                \get_class($v),
                $value->original()->get($i),
            );
            $this->assertSame(
                $v->pack()->toString(),
                $value->original()->get($i)->pack()->toString(),
            );
        }

        $this->assertSame($string, $value->pack()->toString());
    }

    public static function cases(): array
    {
        $map = Map::of(['foo', SignedOctet::of(1)]);

        return [
            [
                \pack('N', 6).\chr(3).'foob'.\chr(1),
                $map,
            ],
            [
                \pack('N', 28).\chr(3).'foob'.\chr(1).\chr(6).'foobarS'.\pack('N', 10).'foo🙏bar',
                $map->put('foobar', LongString::literal('foo🙏bar')),
            ],
        ];
    }
}
