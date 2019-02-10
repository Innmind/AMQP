<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Table,
    Value\SignedOctet,
    Value\LongString,
    Value\Text,
    Value,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Filesystem\Stream\StringStream;
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
            new Table(new Map('string', Value::class))
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type MapInterface<string, Innmind\AMQP\Transport\Frame\Value>
     */
    public function testThrowWhenInvalidMap()
    {
        new Table(new Map('string', 'mixed'));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $map)
    {
        $value = new Table($map);
        $this->assertSame($expected, (string) $value);
        $this->assertSame($map, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($string, $expected)
    {
        $value = Table::fromStream(new StringStream($string));

        $this->assertInstanceOf(Table::class, $value);
        $this->assertCount($expected->size(), $value->original());

        foreach ($expected as $i => $v) {
            $this->assertInstanceOf(
                get_class($v),
                $value->original()->get($i)
            );
            $this->assertSame(
                (string) $v,
                (string) $value->original()->get($i)
            );
        }

        $this->assertSame($string, (string) $value);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\UnboundedTextCannotBeWrapped
     */
    public function testThrowWhenUsingUnboundedText()
    {
        new Table(
            (new Map('string', Value::class))->put(
                'foo',
                new Text(new Str(''))
            )
        );
    }

    public function cases(): array
    {
        $map = (new Map('string', Value::class))
            ->put('foo', new SignedOctet(new Integer(1)));

        return [
            [
                pack('N', 6).chr(3).'foob'.chr(1),
                $map,
            ],
            [
                pack('N', 28).chr(3).'foob'.chr(1).chr(6).'foobarS'.pack('N', 10).'foo🙏bar',
                $map->put('foobar', new LongString(new Str('foo🙏bar'))),
            ],
        ];
    }
}
