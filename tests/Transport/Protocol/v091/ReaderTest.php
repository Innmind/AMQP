<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\Transport\{
    Protocol\v091\Reader,
    Protocol\v091\Methods,
    Frame\Method,
    Frame\Value,
    Frame\Value\ShortString,
    Frame\Value\UnsignedShortInteger,
    Frame\Value\UnsignedLongLongInteger,
    Frame\Value\Bits,
    Frame\Value\UnsignedLongInteger,
    Frame\Value\UnsignedOctet,
    Frame\Value\Table,
    Frame\Value\LongString
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInvokation($method, $arguments)
    {
        $read = new Reader;

        $args = '';

        foreach ($arguments as $arg) {
            $args .= $arg->pack();
        }

        $stream = $read(
            Methods::get($method),
            Stream::ofContent($args)
        );

        $this->assertInstanceOf(Sequence::class, $stream);
        $this->assertSame(Value::class, (string) $stream->type());
        $this->assertCount(count($arguments), $stream);

        foreach ($arguments as $i => $argument) {
            $this->assertInstanceOf(get_class($argument), $stream->get($i));
            $this->assertSame($argument->pack(), $stream->get($i)->pack());
        }
    }

    public function cases(): array
    {
        return [
            [
                'basic.qos-ok',
                [],
            ],
            [
                'basic.consume-ok',
                [new ShortString(Str::of('foo'))],
            ],
            [
                'basic.cancel-ok',
                [new ShortString(Str::of('foo'))],
            ],
            [
                'basic.return',
                [
                    new UnsignedShortInteger(new Integer(42)),
                    new ShortString(Str::of('foo')),
                    new ShortString(Str::of('bar')),
                    new ShortString(Str::of('baz')),
                ],
            ],
            [
                'basic.deliver',
                [
                    new ShortString(Str::of('foo')),
                    new UnsignedLongLongInteger(new Integer(42)),
                    new Bits(true),
                    new ShortString(Str::of('bar')),
                    new ShortString(Str::of('baz')),
                ],
            ],
            [
                'basic.get-ok',
                [
                    new UnsignedLongLongInteger(new Integer(42)),
                    new Bits(true),
                    new ShortString(Str::of('foo')),
                    new ShortString(Str::of('bar')),
                    new UnsignedLongInteger(new Integer(24)),
                ],
            ],
            [
                'basic.get-empty',
                [],
            ],
            [
                'basic.recover-ok',
                [],
            ],
            [
                'channel.open-ok',
                [],
            ],
            [
                'channel.flow',
                [new Bits(true)],
            ],
            [
                'channel.flow-ok',
                [new Bits(true)],
            ],
            [
                'channel.close',
                [
                    new UnsignedShortInteger(new Integer(42)),
                    new ShortString(Str::of('foo')),
                    new UnsignedShortInteger(new Integer(24)),
                    new UnsignedShortInteger(new Integer(66)),
                ],
            ],
            [
                'channel.close-ok',
                [],
            ],
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
            [
                'exchange.declare-ok',
                [],
            ],
            [
                'exchange.delete-ok',
                [],
            ],
            [
                'queue.declare-ok',
                [
                    new ShortString(Str::of('foo')),
                    new UnsignedLongInteger(new Integer(42)),
                    new UnsignedLongInteger(new Integer(24)),
                ],
            ],
            [
                'queue.bind-ok',
                [],
            ],
            [
                'queue.unbind-ok',
                [],
            ],
            [
                'queue.purge-ok',
                [new UnsignedLongInteger(new Integer(42))],
            ],
            [
                'queue.delete-ok',
                [new UnsignedLongInteger(new Integer(42))],
            ],
            [
                'tx.select-ok',
                [],
            ],
            [
                'tx.commit-ok',
                [],
            ],
            [
                'tx.rollback-ok',
                [],
            ],
        ];
    }
}
