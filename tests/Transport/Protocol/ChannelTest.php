<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Protocol\Channel,
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
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class ChannelTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testOpen()
    {
        $frame = (new Channel)->open(
            $channel = new FrameChannel(1),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(20, 10)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(0)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('', $frame->values()->get(0)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testFlow()
    {
        $frame = (new Channel)->flow(
            $channel = new FrameChannel(1),
            Flow::start,
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(20, 20)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(0)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertTrue($frame->values()->get(0)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = (new Channel)->flow(
            $channel = new FrameChannel(1),
            Flow::stop,
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertFalse($frame->values()->get(0)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testFlowOk()
    {
        $frame = (new Channel)->flowOk(
            $channel = new FrameChannel(1),
            FlowOk::of(true),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(20, 21)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(0)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertTrue($frame->values()->get(0)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = (new Channel)->flowOk(
            $channel = new FrameChannel(1),
            FlowOk::of(false),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertFalse($frame->values()->get(0)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testClose()
    {
        $frame = (new Channel)->close(
            $channel = new FrameChannel(1),
            Close::demand(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(20, 40)));
        $this->assertCount(4, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(0, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(2)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(0, $frame->values()->get(2)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(3)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(0, $frame->values()->get(3)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));

        $frame = (new Channel)->close(
            $channel = new FrameChannel(1),
            Close::reply(1, 'foo'),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(1, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertSame('foo', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertSame(0, $frame->values()->get(2)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertSame(0, $frame->values()->get(3)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testCloseOk()
    {
        $frame = (new Channel)->closeOk(
            $channel = new FrameChannel(1),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(20, 41)));
        $this->assertCount(0, $frame->values());
    }
}
