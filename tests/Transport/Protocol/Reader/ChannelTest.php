<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\Reader;

use Innmind\AMQP\{
    Transport\Protocol\Reader\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Str,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInvokation($method, $arguments)
    {
        $read = new Channel;

        $args = '';

        foreach ($arguments as $arg) {
            $args .= $arg->pack();
        }

        $stream = $read(
            Method::of($method),
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

    public function cases(): array
    {
        return [
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
                    new UnsignedShortInteger(Integer::of(42)),
                    new ShortString(Str::of('foo')),
                    new UnsignedShortInteger(Integer::of(24)),
                    new UnsignedShortInteger(Integer::of(66)),
                ],
            ],
            [
                'channel.close-ok',
                [],
            ],
        ];
    }
}
