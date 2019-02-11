<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\Transport\{
    Protocol\v091\Reader\Queue,
    Protocol\v091\Methods,
    Frame\Method,
    Frame\Value,
    Frame\Value\ShortString,
    Frame\Value\UnsignedLongInteger
};
use Innmind\Math\Algebra\Integer;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Str,
    StreamInterface
};
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInvokation($method, $arguments)
    {
        $read = new Queue;

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
        (new Queue)(new Method(0, 0), new StringStream(''));
    }

    public function cases(): array
    {
        return [
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
        ];
    }
}
