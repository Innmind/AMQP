<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\Transport\{
    Protocol\v091\Reader\Channel,
    Protocol\v091\Methods,
    Frame\Method,
    Frame\Value,
    Frame\Value\UnsignedShortInteger,
    Frame\Value\ShortString,
    Frame\Value\Bits
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    StreamInterface
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
        (new Channel)(new Method(0, 0), new Str(''));
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
                    new ShortString(new Str('foo')),
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
