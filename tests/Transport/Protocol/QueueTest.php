<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Protocol\Queue,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Type,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\Table,
    Model\Queue\Declaration,
    Model\Queue\Deletion,
    Model\Queue\Binding,
    Model\Queue\Unbinding,
    Model\Queue\Purge,
};
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    private $queue;

    public function setUp(): void
    {
        $this->queue = new Queue(new ArgumentTranslator);
    }

    public function testDeclare()
    {
        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::passive('foo')
                ->withArgument('foo', 24)
                ->withArgument('bar', 42),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(50, 10)));
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
        $this->assertSame('foo', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(
            [true, false, false, false, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(Table::class, $frame->values()->get(3)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertCount(2, $frame->values()->get(3)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertSame(24, $frame->values()->get(3)->match(
            static fn($value) => $value->original()->get('foo')->match(
                static fn($argument) => $argument->original(),
                static fn() => null,
            ),
            static fn() => null,
        ));
        $this->assertSame(42, $frame->values()->get(3)->match(
            static fn($value) => $value->original()->get('bar')->match(
                static fn($argument) => $argument->original(),
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::durable(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame('', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertSame(
            [false, true, false, false, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::temporary(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame('', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertSame(
            [false, false, false, false, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::autoDelete(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame('', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertSame(
            [false, false, false, true, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()->exclusive(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame('', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertSame(
            [false, false, true, true, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()->dontWait(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame('', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertSame(
            [false, false, false, true, true],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()->withName('foo'),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame('foo', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
    }

    public function testDelete()
    {
        $frame = $this->queue->delete(
            $channel = new Channel(1),
            Deletion::of('foo'),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(50, 40)));
        $this->assertCount(3, $frame->values());
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
        $this->assertSame('foo', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(
            [false, false, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->queue->delete(
            $channel = new Channel(1),
            Deletion::of('foo')->ifUnused(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(
            [true, false, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->queue->delete(
            $channel = new Channel(1),
            Deletion::of('foo')->ifEmpty(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(
            [false, true, false],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->queue->delete(
            $channel = new Channel(1),
            Deletion::of('foo')->dontWait(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertSame(
            [false, false, true],
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );
    }

    public function testBind()
    {
        $frame = $this->queue->bind(
            $channel = new Channel(1),
            Binding::of('ex', 'q', 'rk')
                ->withArgument('foo', 24)
                ->withArgument('bar', 42),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(50, 20)));
        $this->assertCount(6, $frame->values());
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
        $this->assertSame('q', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('ex', $frame->values()->get(2)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(3)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('rk', $frame->values()->get(3)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(4)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertFalse($frame->values()->get(4)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
        $this->assertInstanceOf(Table::class, $frame->values()->get(5)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertCount(2, $frame->values()->get(5)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertSame(24, $frame->values()->get(5)->match(
            static fn($value) => $value->original()->get('foo')->match(
                static fn($argument) => $argument->original(),
                static fn() => null,
            ),
            static fn() => null,
        ));
        $this->assertSame(42, $frame->values()->get(5)->match(
            static fn($value) => $value->original()->get('bar')->match(
                static fn($argument) => $argument->original(),
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = $this->queue->bind(
            $channel = new Channel(1),
            Binding::of('ex', 'q', 'rk')->dontWait(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertTrue($frame->values()->get(4)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }

    public function testUnbind()
    {
        $frame = $this->queue->unbind(
            $channel = new Channel(1),
            Unbinding::of('ex', 'q', 'rk')
                ->withArgument('foo', 24)
                ->withArgument('bar', 42),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(50, 50)));
        $this->assertCount(5, $frame->values());
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
        $this->assertSame('q', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('ex', $frame->values()->get(2)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(3)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('rk', $frame->values()->get(3)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Table::class, $frame->values()->get(4)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertCount(2, $frame->values()->get(4)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertSame(24, $frame->values()->get(4)->match(
            static fn($value) => $value->original()->get('foo')->match(
                static fn($argument) => $argument->original(),
                static fn() => null,
            ),
            static fn() => null,
        ));
        $this->assertSame(42, $frame->values()->get(4)->match(
            static fn($value) => $value->original()->get('bar')->match(
                static fn($argument) => $argument->original(),
                static fn() => null,
            ),
            static fn() => null,
        ));
    }

    public function testPurge()
    {
        $frame = $this->queue->purge(
            $channel = new Channel(1),
            Purge::of('q'),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(50, 30)));
        $this->assertCount(3, $frame->values());
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
        $this->assertSame('q', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertFalse($frame->values()->get(2)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = $this->queue->purge(
            $channel = new Channel(1),
            Purge::of('q')->dontWait(),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertTrue($frame->values()->get(2)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }
}
