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
use Innmind\Math\Algebra\Integer;
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
            new Table(Map::of('string', Value::class))
        );
    }

    public function testThrowWhenInvalidMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, Innmind\AMQP\Transport\Frame\Value>');

        new Table(Map::of('string', 'mixed'));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $map)
    {
        $value = new Table($map);
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
                get_class($v),
                $value->original()->get($i)
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

        new Table(
            Map::of('string', Value::class)
                ('foo', new Text(Str::of('')))
        );
    }

    public function cases(): array
    {
        $map = Map::of('string', Value::class)
            ('foo', new SignedOctet(new Integer(1)));

        return [
            [
                pack('N', 6).chr(3).'foob'.chr(1),
                $map,
            ],
            [
                pack('N', 28).chr(3).'foob'.chr(1).chr(6).'foobarS'.pack('N', 10).'foo🙏bar',
                $map->put('foobar', new LongString(Str::of('foo🙏bar'))),
            ],
        ];
    }
}
