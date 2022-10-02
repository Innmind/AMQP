<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Protocol\v091\Reader\Basic,
    Transport\Protocol\v091\Methods,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\UnsignedLongInteger,
    Exception\UnknownMethod,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Str,
    Sequence,
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

        $args = '';

        foreach ($arguments as $arg) {
            $args .= $arg->pack();
        }

        $stream = $read(
            Methods::get($method),
            Stream::ofContent($args),
        );

        $this->assertInstanceOf(Sequence::class, $stream);
        $this->assertCount(\count($arguments), $stream);

        foreach ($arguments as $i => $argument) {
            $this->assertInstanceOf(\get_class($argument), $stream->get($i)->match(
                static fn($value) => $value,
                static fn() => null,
            ));
            $this->assertSame($argument->pack(), $stream->get($i)->match(
                static fn($value) => $value->pack(),
                static fn() => null,
            ));
        }
    }

    public function testThrowWhenUnknownMethod()
    {
        $this->expectException(UnknownMethod::class);
        $this->expectExceptionMessage('0,0');

        (new Basic)(new Method(0, 0), Stream::ofContent(''));
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
                    new UnsignedShortInteger(Integer::of(42)),
                    new ShortString(Str::of('foo')),
                    new ShortString(Str::of('bar')),
                    new ShortString(Str::of('baz')),
                ],
            ],
            [
                'basic.deliver',
                [
                    new ShortString(Str::of('foo')),
                    new UnsignedLongLongInteger(Integer::of(42)),
                    new Bits(true),
                    new ShortString(Str::of('bar')),
                    new ShortString(Str::of('baz')),
                ],
            ],
            [
                'basic.get-ok',
                [
                    new UnsignedLongLongInteger(Integer::of(42)),
                    new Bits(true),
                    new ShortString(Str::of('foo')),
                    new ShortString(Str::of('bar')),
                    new UnsignedLongInteger(Integer::of(24)),
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
