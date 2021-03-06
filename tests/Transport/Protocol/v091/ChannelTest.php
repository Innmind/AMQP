<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Transport\Protocol\v091\Channel,
    Transport\Protocol\Channel as ChannelInterface,
    Transport\Frame,
    Transport\Frame\Type,
    Transport\Frame\Method,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\UnsignedShortInteger,
    Model\Channel\Flow,
    Model\Channel\FlowOk,
    Model\Channel\Close,
};
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(ChannelInterface::class, new Channel);
    }

    public function testOpen()
    {
        $frame = (new Channel)->open(
            $channel = new FrameChannel(1)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(20, 10)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(0));
        $this->assertSame('', $frame->values()->get(0)->original()->toString());
    }

    public function testFlow()
    {
        $frame = (new Channel)->flow(
            $channel = new FrameChannel(1),
            Flow::start()
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(20, 20)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(0));
        $this->assertTrue($frame->values()->get(0)->original()->first());

        $frame = (new Channel)->flow(
            $channel = new FrameChannel(1),
            Flow::stop()
        );

        $this->assertFalse($frame->values()->get(0)->original()->first());
    }

    public function testFlowOk()
    {
        $frame = (new Channel)->flowOk(
            $channel = new FrameChannel(1),
            new FlowOk(true)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(20, 21)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(0));
        $this->assertTrue($frame->values()->get(0)->original()->first());

        $frame = (new Channel)->flowOk(
            $channel = new FrameChannel(1),
            new FlowOk(false)
        );

        $this->assertFalse($frame->values()->get(0)->original()->first());
    }

    public function testClose()
    {
        $frame = (new Channel)->close(
            $channel = new FrameChannel(1),
            new Close
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(20, 40)));
        $this->assertCount(4, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('', $frame->values()->get(1)->original()->toString());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(2)
        );
        $this->assertSame(0, $frame->values()->get(2)->original()->value());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(3)
        );
        $this->assertSame(0, $frame->values()->get(3)->original()->value());

        $frame = (new Channel)->close(
            $channel = new FrameChannel(1),
            Close::reply(1, 'foo')->causedBy('channel.close')
        );

        $this->assertSame(1, $frame->values()->get(0)->original()->value());
        $this->assertSame('foo', $frame->values()->get(1)->original()->toString());
        $this->assertSame(20, $frame->values()->get(2)->original()->value());
        $this->assertSame(40, $frame->values()->get(3)->original()->value());
    }

    public function testCloseOk()
    {
        $frame = (new Channel)->closeOk(
            $channel = new FrameChannel(1)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(20, 41)));
        $this->assertCount(0, $frame->values());
    }
}
