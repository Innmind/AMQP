<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Transport\Protocol\v091\Queue,
    Transport\Protocol\Queue as QueueInterface,
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
    Model\Queue\Purge
};
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(QueueInterface::class, new Queue);
    }

    public function testDeclare()
    {
        $frame = (new Queue)->declare(
            $channel = new Channel(1),
            Declaration::passive('foo')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(50, 10)));
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

        $frame = (new Queue)->declare(
            $channel = new Channel(1),
            Declaration::durable()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, true, false, false, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = (new Queue)->declare(
            $channel = new Channel(1),
            Declaration::temporary()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, false, false, false, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = (new Queue)->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, false, false, true, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = (new Queue)->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()->exclusive()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, false, true, true, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = (new Queue)->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()->dontWait()
        );

        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertSame(
            [false, false, false, true, true],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = (new Queue)->declare(
            $channel = new Channel(1),
            Declaration::autoDelete()->withName('foo')
        );

        $this->assertSame('foo', (string) $frame->values()->get(1)->original());
    }

    public function testDelete()
    {
        $frame = (new Queue)->delete(
            $channel = new Channel(1),
            new Deletion('foo')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(50, 40)));
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

        $frame = (new Queue)->delete(
            $channel = new Channel(1),
            (new Deletion('foo'))->ifUnused()
        );

        $this->assertSame(
            [true, false, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = (new Queue)->delete(
            $channel = new Channel(1),
            (new Deletion('foo'))->ifEmpty()
        );

        $this->assertSame(
            [false, true, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = (new Queue)->delete(
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
        $frame = (new Queue)->bind(
            $channel = new Channel(1),
            new Binding('ex', 'q', 'rk')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(50, 20)));
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

        $frame = (new Queue)->bind(
            $channel = new Channel(1),
            (new Binding('ex', 'q', 'rk'))->dontWait()
        );

        $this->assertTrue($frame->values()->get(4)->original()->first());
    }

    public function testUnbind()
    {
        $frame = (new Queue)->unbind(
            $channel = new Channel(1),
            new Unbinding('ex', 'q', 'rk')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(50, 50)));
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
    }

    public function testPurge()
    {
        $frame = (new Queue)->purge(
            $channel = new Channel(1),
            new Purge('q')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(50, 30)));
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

        $frame = (new Queue)->purge(
            $channel = new Channel(1),
            (new Purge('q'))->dontWait()
        );

        $this->assertTrue($frame->values()->get(2)->original()->first());
    }
}
