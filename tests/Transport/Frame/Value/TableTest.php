<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Table,
    Value\SignedOctet,
    Value\LongString,
    Value
};
use Innmind\Immutable\{
    Map,
    Str
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
        $this->assertSame(
            $expected,
            (string) new Table($map)
        );
    }

    public function cases(): array
    {
        $map = (new Map('string', Value::class))
            ->put('foo', new SignedOctet(1));

        return [
            [
                pack('N', 5).chr(3).'foo'.chr(1),
                $map,
            ],
            [
                pack('N', 26).chr(3).'foo'.chr(1).chr(6).'foobar'.pack('N', 10).'foo🙏bar',
                $map->put('foobar', new LongString(new Str('foo🙏bar'))),
            ],
        ];
    }
}
