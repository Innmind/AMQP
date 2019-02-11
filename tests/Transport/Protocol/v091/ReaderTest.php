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
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Str,
    StreamInterface,
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

        $stream = $read(
            Methods::get($method),
            new StringStream(implode('', $arguments))
        );

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame(Value::class, (string) $stream->type());
        $this->assertCount(count($arguments), $stream);

        foreach ($arguments as $i => $argument) {
            $this->assertInstanceOf(get_class($argument), $stream->get($i));
            $this->assertSame((string) $argument, (string) $stream->get($i));
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
                [new ShortString(new Str('foo'))],
            ],
            [
                'basic.cancel-ok',
                [new ShortString(new Str('foo'))],
            ],
            [
                'basic.return',
                [
                    new UnsignedShortInteger(new Integer(42)),
                    new ShortString(new Str('foo')),
                    new ShortString(new Str('bar')),
                    new ShortString(new Str('baz')),
                ],
            ],
            [
                'basic.deliver',
                [
                    new ShortString(new Str('foo')),
                    new UnsignedLongLongInteger(new Integer(42)),
                    new Bits(true),
                    new ShortString(new Str('bar')),
                    new ShortString(new Str('baz')),
                ],
            ],
            [
                'basic.get-ok',
                [
                    new UnsignedLongLongInteger(new Integer(42)),
                    new Bits(true),
                    new ShortString(new Str('foo')),
                    new ShortString(new Str('bar')),
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
                    new ShortString(new Str('foo')),
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
                    new Table(new Map('string', Value::class)),
                    new LongString(new Str('foo')),
                    new LongString(new Str('bar')),
                ],
            ],
            [
                'connection.secure',
                [new LongString(new Str('foo'))],
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
                    new ShortString(new Str('foo')),
                ],
            ],
            [
                'connection.close',
                [
                    new UnsignedShortInteger(new Integer(0)),
                    new ShortString(new Str('foo')),
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
                    new ShortString(new Str('foo')),
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
