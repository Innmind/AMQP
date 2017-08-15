<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\Transport\{
    Protocol\v091\Reader\Basic,
    Protocol\v091\Methods,
    Frame\Method,
    Frame\Value,
    Frame\Value\UnsignedShortInteger,
    Frame\Value\ShortString,
    Frame\Value\UnsignedLongLongInteger,
    Frame\Value\Bits,
    Frame\Value\UnsignedLongInteger
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    StreamInterface
};
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInvokation($method, $arguments)
    {
        $read = new Basic;

        $stream = $read(
            Methods::get($method),
            new Str(implode('', $arguments))
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
        (new Basic)(new Method(0, 0), new Str(''));
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
        ];
    }
}
