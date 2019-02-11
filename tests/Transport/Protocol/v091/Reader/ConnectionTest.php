<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\Transport\{
    Protocol\v091\Reader\Connection,
    Protocol\v091\Methods,
    Frame\Method,
    Frame\Value,
    Frame\Value\UnsignedOctet,
    Frame\Value\Table,
    Frame\Value\LongString,
    Frame\Value\UnsignedShortInteger,
    Frame\Value\UnsignedLongInteger,
    Frame\Value\ShortString
};
use Innmind\Math\Algebra\Integer;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Str,
    StreamInterface,
    Map
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

    /**
     * @expectedException Innmind\AMQP\Exception\UnknownMethod
     * @expectedExceptionMessage 0,0
     */
    public function testThrowWhenUnknownMethod()
    {
        (new Connection)(new Method(0, 0), new StringStream(''));
    }

    public function cases(): array
    {
        return [
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
        ];
    }
}
