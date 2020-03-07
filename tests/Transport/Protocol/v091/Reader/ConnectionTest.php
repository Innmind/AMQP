<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Protocol\v091\Reader\Connection,
    Transport\Protocol\v091\Methods,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\ShortString,
    Exception\UnknownMethod,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInvokation($method, $arguments)
    {
        $read = new Connection;

        $args = '';

        foreach ($arguments as $arg) {
            $args .= $arg->pack();
        }

        $stream = $read(
            Methods::get($method),
            Stream::ofContent($args),
        );

        $this->assertInstanceOf(Sequence::class, $stream);
        $this->assertSame(Value::class, (string) $stream->type());
        $this->assertCount(count($arguments), $stream);

        foreach ($arguments as $i => $argument) {
            $this->assertInstanceOf(get_class($argument), $stream->get($i));
            $this->assertSame($argument->pack(), $stream->get($i)->pack());
        }
    }

    public function testThrowWhenUnknownMethod()
    {
        $this->expectException(UnknownMethod::class);
        $this->expectExceptionMessage('0,0');

        (new Connection)(new Method(0, 0), Stream::ofContent(''));
    }

    public function cases(): array
    {
        return [
            [
                'connection.start',
                [
                    new UnsignedOctet(new Integer(0)),
                    new UnsignedOctet(new Integer(9)),
                    new Table(Map::of('string', Value::class)),
                    new LongString(Str::of('foo')),
                    new LongString(Str::of('bar')),
                ],
            ],
            [
                'connection.secure',
                [new LongString(Str::of('foo'))],
            ],
            [
                'connection.tune',
                [
                    new UnsignedShortInteger(new Integer(1)),
                    new UnsignedLongInteger(new Integer(2)),
                    new UnsignedShortInteger(new Integer(3)),
                ],
            ],
            [
                'connection.open-ok',
                [
                    new ShortString(Str::of('foo')),
                ],
            ],
            [
                'connection.close',
                [
                    new UnsignedShortInteger(new Integer(0)),
                    new ShortString(Str::of('foo')),
                    new UnsignedShortInteger(new Integer(1)),
                    new UnsignedShortInteger(new Integer(2)),
                ],
            ],
            [
                'connection.close-ok',
                [],
            ],
        ];
    }
}
