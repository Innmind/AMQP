<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Transport\Protocol\v091\Basic,
    Transport\Protocol\Basic as BasicInterface,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Type,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\Table,
    Model\Basic\Ack,
    Model\Basic\Cancel,
    Model\Basic\Consume,
    Model\Basic\Get,
    Model\Basic\Publish,
    Model\Basic\Qos,
    Model\Basic\Recover,
    Model\Basic\Reject
};
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(BasicInterface::class, new Basic);
    }

    public function testAck()
    {
        $frame = (new Basic)->ack(
            $channel = new Channel(1),
            new Ack(42)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(60, 80)));
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(42, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(1));
        $this->assertFalse($frame->values()->get(1)->original()->first());

        $frame = (new Basic)->ack(
            $channel = new Channel(1),
            Ack::multiple(42)
        );

        $this->assertTrue($frame->values()->get(1)->original()->first());
    }

    public function testCancel()
    {
        $frame = (new Basic)->cancel(
            $channel = new Channel(1),
            new Cancel('consumer')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(60, 30)));
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(0));
        $this->assertSame('consumer', (string) $frame->values()->get(0)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(1));
        $this->assertFalse($frame->values()->get(1)->original()->first());

        $frame = (new Basic)->cancel(
            $channel = new Channel(1),
            (new Cancel('consumer'))->dontWait()
        );

        $this->assertTrue($frame->values()->get(1)->original()->first());
    }

    public function testConsume()
    {
        $frame = (new Basic)->consume(
            $channel = new Channel(1),
            new Consume('queue')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(60, 20)));
        $this->assertCount(8, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('queue', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2));
        $this->assertSame('', (string) $frame->values()->get(2)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(3));
        $this->assertFalse($frame->values()->get(3)->original()->first());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(4));
        $this->assertFalse($frame->values()->get(4)->original()->first());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(5));
        $this->assertFalse($frame->values()->get(5)->original()->first());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(6));
        $this->assertFalse($frame->values()->get(6)->original()->first());
        $this->assertInstanceOf(Table::class, $frame->values()->get(7));

        $frame = (new Basic)->consume(
            $channel = new Channel(1),
            (new Consume('queue'))->withConsumerTag('tag')
        );

        $this->assertSame('tag', (string) $frame->values()->get(2)->original());

        $frame = (new Basic)->consume(
            $channel = new Channel(1),
            (new Consume('queue'))->noLocal()
        );

        $this->assertTrue($frame->values()->get(3)->original()->first());

        $frame = (new Basic)->consume(
            $channel = new Channel(1),
            (new Consume('queue'))->autoAcknowledge()
        );

        $this->assertTrue($frame->values()->get(4)->original()->first());

        $frame = (new Basic)->consume(
            $channel = new Channel(1),
            (new Consume('queue'))->exclusive()
        );

        $this->assertTrue($frame->values()->get(5)->original()->first());

        $frame = (new Basic)->consume(
            $channel = new Channel(1),
            (new Consume('queue'))->dontWait()
        );

        $this->assertTrue($frame->values()->get(6)->original()->first());
    }

    public function testGet()
    {
        $frame = (new Basic)->get(
            $channel = new Channel(1),
            new Get('queue')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(60, 70)));
        $this->assertCount(3, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('queue', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2));
        $this->assertFalse($frame->values()->get(2)->original()->first());

        $frame = (new Basic)->get(
            $channel = new Channel(1),
            (new Get('queue'))->autoAcknowledge()
        );

        $this->assertTrue($frame->values()->get(2)->original()->first());
    }

    public function testPublish()
    {
        $frame = (new Basic)->publish(
            $channel = new Channel(1),
            new Publish
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(60, 40)));
        $this->assertCount(5, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2));
        $this->assertSame('', (string) $frame->values()->get(2)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(3));
        $this->assertFalse($frame->values()->get(3)->original()->first());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(4));
        $this->assertFalse($frame->values()->get(4)->original()->first());

        $frame = (new Basic)->publish(
            $channel = new Channel(1),
            (new Publish)->to('foo')
        );

        $this->assertSame('foo', (string) $frame->values()->get(1)->original());

        $frame = (new Basic)->publish(
            $channel = new Channel(1),
            (new Publish)->withRoutingKey('foo')
        );

        $this->assertSame('foo', (string) $frame->values()->get(2)->original());

        $frame = (new Basic)->publish(
            $channel = new Channel(1),
            (new Publish)->flagAsMandatory()
        );

        $this->assertTrue($frame->values()->get(3)->original()->first());

        $frame = (new Basic)->publish(
            $channel = new Channel(1),
            (new Publish)->flagAsImmediate()
        );

        $this->assertTrue($frame->values()->get(4)->original()->first());
    }

    public function testQos()
    {
        $frame = (new Basic)->qos(
            $channel = new Channel(1),
            new Qos(1, 2)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(60, 10)));
        $this->assertCount(3, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(1, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(1)
        );
        $this->assertSame(2, $frame->values()->get(1)->original()->value());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2));
        $this->assertFalse($frame->values()->get(2)->original()->first());

        $frame = (new Basic)->qos(
            $channel = new Channel(1),
            Qos::global(1, 2)
        );

        $this->asserttrue($frame->values()->get(2)->original()->first());
    }

    public function testRecover()
    {
        $frame = (new Basic)->recover(
            $channel = new Channel(1),
            new Recover
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(60, 110)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(0));
        $this->assertFalse($frame->values()->get(0)->original()->first());

        $frame = (new Basic)->recover(
            $channel = new Channel(1),
            Recover::requeue()
        );

        $this->assertTrue($frame->values()->get(0)->original()->first());
    }

    public function testReject()
    {
        $frame = (new Basic)->reject(
            $channel = new Channel(1),
            new Reject(42)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(60, 90)));
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(42, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(1));
        $this->assertFalse($frame->values()->get(1)->original()->first());

        $frame = (new Basic)->reject(
            $channel = new Channel(1),
            Reject::requeue(42)
        );

        $this->assertTrue($frame->values()->get(1)->original()->first());
    }
}
