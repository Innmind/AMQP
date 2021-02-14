<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport;

use Innmind\AMQP\Transport\{
    Frame,
    Frame\Type,
    Frame\Channel,
    Frame\Method,
    Frame\Value,
    Frame\Value\Bits,
    Frame\Value\Text,
};
use Innmind\Immutable\{
    Str,
    Sequence,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class FrameTest extends TestCase
{
    public function testMethod()
    {
        $frame = Frame::method(
            $channel = new Channel(42),
            $method = new Method(10, 10),
            $bit = new Bits(true),
            $text = new Text(Str::of('foobar'))
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is($method));
        $this->assertInstanceOf(Sequence::class, $frame->values());
        $this->assertSame(Value::class, (string) $frame->values()->type());
        $this->assertSame([$bit, $text], unwrap($frame->values()));
        $this->assertSame(
            \chr(1).\pack('n', 42).\pack('N', 11).\pack('n', 10).\pack('n', 10).$bit->pack().$text->pack().\chr(0xCE),
            $frame->toString(),
        );
    }

    public function testHeader()
    {
        $frame = Frame::header(
            $channel = new Channel(42),
            60,
            $value = new Text(Str::of('foobar'))
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::header(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertFalse($frame->is(new Method(0, 0)));
        $this->assertInstanceOf(Sequence::class, $frame->values());
        $this->assertSame(Value::class, (string) $frame->values()->type());
        $this->assertSame([$value], unwrap($frame->values()));
        $this->assertSame(
            \chr(2).\pack('n', 42).\pack('N', 10).\pack('n', 60).\pack('n', 0).'foobar'.\chr(0xCE),
            $frame->toString(),
        );
    }

    public function testBody()
    {
        $frame = Frame::body(
            $channel = new Channel(42),
            $text = Str::of('foobar')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame($channel, $frame->channel());
        $this->assertFalse($frame->is(new Method(0, 0)));
        $this->assertInstanceOf(Sequence::class, $frame->values());
        $this->assertSame(Value::class, (string) $frame->values()->type());
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Text::class, $frame->values()->first());
        $this->assertSame($text, $frame->values()->first()->original());
        $this->assertSame(
            \chr(3).\pack('n', 42).\pack('N', 6).'foobar'.\chr(0xCE),
            $frame->toString(),
        );
    }

    public function testHeartbeat()
    {
        $frame = Frame::heartbeat();

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertInstanceOf(Channel::class, $frame->channel());
        $this->assertSame(0, $frame->channel()->toInt());
        $this->assertFalse($frame->is(new Method(0, 0)));
        $this->assertInstanceOf(Sequence::class, $frame->values());
        $this->assertSame(Value::class, (string) $frame->values()->type());
        $this->assertCount(0, $frame->values());
        $this->assertSame(
            \chr(8).\pack('n', 0).\pack('N', 0).\chr(0xCE),
            $frame->toString(),
        );
    }
}
