<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Transport\Protocol\v091\Queue,
    Transport\Protocol\Queue as QueueInterface,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Type,
    Transport\Frame\Value,
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
use Innmind\Math\Algebra\Integer;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    private $queue;
    private $translator;

    public function setUp(): void
    {
        $this->queue = new Queue(
            $this->translator = $this->createMock(ArgumentTranslator::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(QueueInterface::class, $this->queue);
    }

    public function testDeclare()
    {
        $this
            ->translator
            ->expects($this->at(0))
            ->method('__invoke')
            ->with(24)
            ->willReturn($firstArgument = new UnsignedShortInteger(
                new Integer(24)
            ));
        $this
            ->translator
            ->expects($this->at(1))
            ->method('__invoke')
            ->with(42)
            ->willReturn($secondArgument = new UnsignedShortInteger(
                new Integer(42)
            ));
        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::passive('foo')
                ->withArgument('foo', 24)
                ->withArgument('bar', 42)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(50, 10)));
        $this->assertCount(4, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('foo', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2));
        $this->assertSame(
            [true, false, false, false, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );
        $this->assertInstanceOf(Table::class, $frame->values()->get(3));
        $this->assertCount(2, $frame->values()->get(3)->original());
        $this->assertSame($firstArgument, $frame->values()->get(3)->original()->get('foo'));
        $this->assertSame($secondArgument, $frame->values()->get(3)->original()->get('bar'));

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::durable()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, true, false, false, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::temporary()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, false, false, false, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, false, false, true, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()->exclusive()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, false, true, true, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()->dontWait()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, false, false, true, true],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = $this->queue->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()->withName('foo')
        );

        $this->assertSame('foo', (string) $frame->values()->get(1)->original());
    }

    public function testDelete()
    {
        $frame = $this->queue->delete(
            $channel = new Channel(1),
            new Deletion('foo')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(50, 40)));
        $this->assertCount(3, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('foo', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2));
        $this->assertSame(
            [false, false, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = $this->queue->delete(
            $channel = new Channel(1),
            (new Deletion('foo'))->ifUnused()
        );

        $this->assertSame(
            [true, false, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = $this->queue->delete(
            $channel = new Channel(1),
            (new Deletion('foo'))->ifEmpty()
        );

        $this->assertSame(
            [false, true, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = $this->queue->delete(
            $channel = new Channel(1),
            (new Deletion('foo'))->dontWait()
        );

        $this->assertSame(
            [false, false, true],
            $frame->values()->get(2)->original()->toPrimitive()
        );
    }

    public function testBind()
    {
        $this
            ->translator
            ->expects($this->at(0))
            ->method('__invoke')
            ->with(24)
            ->willReturn($firstArgument = new UnsignedShortInteger(
                new Integer(24)
            ));
        $this
            ->translator
            ->expects($this->at(1))
            ->method('__invoke')
            ->with(42)
            ->willReturn($secondArgument = new UnsignedShortInteger(
                new Integer(42)
            ));
        $frame = $this->queue->bind(
            $channel = new Channel(1),
            (new Binding('ex', 'q', 'rk'))
                ->withArgument('foo', 24)
                ->withArgument('bar', 42)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(50, 20)));
        $this->assertCount(6, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('q', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2));
        $this->assertSame('ex', (string) $frame->values()->get(2)->original());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(3));
        $this->assertSame('rk', (string) $frame->values()->get(3)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(4));
        $this->assertFalse($frame->values()->get(4)->original()->first());
        $this->assertInstanceOf(Table::class, $frame->values()->get(5));
        $this->assertCount(2, $frame->values()->get(5)->original());
        $this->assertSame($firstArgument, $frame->values()->get(5)->original()->get('foo'));
        $this->assertSame($secondArgument, $frame->values()->get(5)->original()->get('bar'));

        $frame = $this->queue->bind(
            $channel = new Channel(1),
            (new Binding('ex', 'q', 'rk'))->dontWait()
        );

        $this->assertTrue($frame->values()->get(4)->original()->first());
    }

    public function testUnbind()
    {
        $this
            ->translator
            ->expects($this->at(0))
            ->method('__invoke')
            ->with(24)
            ->willReturn($firstArgument = new UnsignedShortInteger(
                new Integer(24)
            ));
        $this
            ->translator
            ->expects($this->at(1))
            ->method('__invoke')
            ->with(42)
            ->willReturn($secondArgument = new UnsignedShortInteger(
                new Integer(42)
            ));
        $frame = $this->queue->unbind(
            $channel = new Channel(1),
            (new Unbinding('ex', 'q', 'rk'))
                ->withArgument('foo', 24)
                ->withArgument('bar', 42)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(50, 50)));
        $this->assertCount(5, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('q', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2));
        $this->assertSame('ex', (string) $frame->values()->get(2)->original());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(3));
        $this->assertSame('rk', (string) $frame->values()->get(3)->original());
        $this->assertInstanceOf(Table::class, $frame->values()->get(4));
        $this->assertCount(2, $frame->values()->get(4)->original());
        $this->assertSame($firstArgument, $frame->values()->get(4)->original()->get('foo'));
        $this->assertSame($secondArgument, $frame->values()->get(4)->original()->get('bar'));
    }

    public function testPurge()
    {
        $frame = $this->queue->purge(
            $channel = new Channel(1),
            new Purge('q')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(50, 30)));
        $this->assertCount(3, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('q', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2));
        $this->assertFalse($frame->values()->get(2)->original()->first());

        $frame = $this->queue->purge(
            $channel = new Channel(1),
            (new Purge('q'))->dontWait()
        );

        $this->assertTrue($frame->values()->get(2)->original()->first());
    }
}
