<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Protocol\v091\Reader\Channel,
    Transport\Protocol\v091\Methods,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Exception\UnknownMethod,
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

        (new Channel)(new Method(0, 0), Stream::ofContent(''));
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
        ];
    }
}
