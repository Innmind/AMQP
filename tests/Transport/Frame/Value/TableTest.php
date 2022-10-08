<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\Table,
    Transport\Frame\Value\SignedOctet,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\Text,
    Transport\Frame\Value,
    Exception\UnboundedTextCannotBeWrapped,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            Table::of(Map::of()),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $map)
    {
        $value = Table::of($map);
        $this->assertSame($expected, $value->pack());
        $this->assertSame($map, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($string, $expected)
    {
        $value = Table::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(Table::class, $value);
        $this->assertCount($expected->size(), $value->original());

        foreach ($expected as $i => $v) {
            $this->assertInstanceOf(
                \get_class($v),
                $value->original()->get($i),
            );
            $this->assertSame(
                $v->pack(),
                $value->original()->get($i)->pack(),
            );
        }

        $this->assertSame($string, $value->pack());
    }

    public function testThrowWhenUsingUnboundedText()
    {
        $this->expectException(UnboundedTextCannotBeWrapped::class);

        Table::of(
            Map::of(['foo', Text::of(Str::of(''))]),
        );
    }

    public function cases(): array
    {
        $map = Map::of(['foo', SignedOctet::of(1)]);

        return [
            [
                \pack('N', 6).\chr(3).'foob'.\chr(1),
                $map,
            ],
            [
                \pack('N', 28).\chr(3).'foob'.\chr(1).\chr(6).'foobarS'.\pack('N', 10).'foo🙏bar',
                $map->put('foobar', LongString::of(Str::of('foo🙏bar'))),
            ],
        ];
    }
}
