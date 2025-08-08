<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\Table,
    Transport\Frame\Value\SignedOctet,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\IO\IO;
use Innmind\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TableTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            Table::of(Map::of()),
        );
    }

    #[DataProvider('cases')]
    public function testStringCast($expected, $map)
    {
        $value = Table::of($map);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertSame($map, $value->original());
    }

    #[DataProvider('cases')]
    public function testFromStream($string, $expected)
    {
        $value = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::ofContent($string))
            ->toEncoding(Str\Encoding::ascii)
            ->frames(Table::frame(new Clock))
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
