<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport;

use Innmind\AMQP\Transport\{
    Frame,
    Frame\Type,
    Frame\Channel,
    Frame\Method,
    Frame\MethodClass,
    Frame\Value\Bits,
    Frame\Value\LongString,
};
use Innmind\Immutable\{
    Str,
    Sequence,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class FrameTest extends TestCase
{
    public function testMethod()
    {
        $frame = Frame::method(
            $channel = new Channel(42),
            $method = Method::of(10, 10),
            $bit = Bits::of(true),
            $text = LongString::of(Str::of('foobar')),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is($method));
        $this->assertInstanceOf(Sequence::class, $frame->values());
        $this->assertSame([$bit, $text], $frame->values()->toList());
        $this->assertSame(
            \chr(1).\pack('n', 42).\pack('N', 15).\pack('n', 10).\pack('n', 10).$bit->pack()->toString().$text->pack()->toString().\chr(0xCE),
            $frame->pack()->toString(),
        );
    }

    public function testHeader()
    {
        $frame = Frame::header(
            $channel = new Channel(42),
            MethodClass::basic,
            $value = LongString::of(Str::of('foobar')),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::header, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertInstanceOf(Sequence::class, $frame->values());
        $this->assertSame([$value], $frame->values()->toList());
        $this->assertSame(
            \chr(2).\pack('n', 42).\pack('N', 14).\pack('n', 60).\pack('n', 0).$value->pack()->toString().\chr(0xCE),
            $frame->pack()->toString(),
        );
    }

    public function testBody()
    {
        $frame = Frame::body(
            $channel = new Channel(42),
            $text = Str::of('foobar'),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame($channel, $frame->channel());
        $this->assertInstanceOf(Sequence::class, $frame->values());
        $this->assertCount(0, $frame->values());
        $this->assertSame($text, $frame->content()->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(
            \chr(3).\pack('n', 42).\pack('N', 6).'foobar'.\chr(0xCE),
            $frame->pack()->toString(),
        );
    }

    public function testHeartbeat()
    {
        $frame = Frame::heartbeat();

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertInstanceOf(Channel::class, $frame->channel());
        $this->assertSame(0, $frame->channel()->toInt());
        $this->assertInstanceOf(Sequence::class, $frame->values());
        $this->assertCount(0, $frame->values());
        $this->assertSame(
            \chr(8).\pack('n', 0).\pack('N', 0).\chr(0xCE),
            $frame->pack()->toString(),
        );
    }
}
