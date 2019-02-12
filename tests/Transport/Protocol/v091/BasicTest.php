<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Transport\Protocol\v091\Basic,
    Transport\Protocol\Basic as BasicInterface,
    Transport\Protocol\ArgumentTranslator,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
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
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\Timestamp,
    Model\Basic\Ack,
    Model\Basic\Cancel,
    Model\Basic\Consume,
    Model\Basic\Get,
    Model\Basic\Publish,
    Model\Basic\Qos,
    Model\Basic\Recover,
    Model\Basic\Reject,
    Model\Basic\Message\Generic,
    Model\Basic\Message\AppId,
    Model\Basic\Message\ContentType,
    Model\Basic\Message\ContentEncoding,
    Model\Basic\Message\CorrelationId,
    Model\Basic\Message\DeliveryMode,
    Model\Basic\Message\Id,
    Model\Basic\Message\Priority,
    Model\Basic\Message\ReplyTo,
    Model\Basic\Message\Type as MessageType,
    Model\Basic\Message\UserId,
    Model\Connection\MaxFrameSize,
};
use Innmind\Math\Algebra\Integer;
use Innmind\TimeContinuum\{
    PointInTime\Earth\Now,
    ElapsedPeriod,
};
use Innmind\Immutable\{
    Str,
    StreamInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    private $basic;
    private $translator;

    public function setUp(): void
    {
        $this->basic = new Basic(
            $this->translator = $this->createMock(ArgumentTranslator::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(BasicInterface::class, $this->basic);
    }

    public function testAck()
    {
        $frame = $this->basic->ack(
            $channel = new Channel(1),
            new Ack(42)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(60, 80)));
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(42, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(1));
        $this->assertFalse($frame->values()->get(1)->original()->first());

        $frame = $this->basic->ack(
            $channel = new Channel(1),
            Ack::multiple(42)
        );

        $this->assertTrue($frame->values()->get(1)->original()->first());
    }

    public function testCancel()
    {
        $frame = $this->basic->cancel(
            $channel = new Channel(1),
            new Cancel('consumer')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(60, 30)));
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(0));
        $this->assertSame('consumer', (string) $frame->values()->get(0)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(1));
        $this->assertFalse($frame->values()->get(1)->original()->first());

        $frame = $this->basic->cancel(
            $channel = new Channel(1),
            (new Cancel('consumer'))->dontWait()
        );

        $this->assertTrue($frame->values()->get(1)->original()->first());
    }

    public function testConsume()
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
        $frame = $this->basic->consume(
            $channel = new Channel(1),
            (new Consume('queue'))
                ->withArgument('foo', 24)
                ->withArgument('bar', 42)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(60, 20)));
        $this->assertCount(5, $frame->values());
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
        $this->assertSame(
            [false, false, false, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );
        $this->assertInstanceOf(Table::class, $frame->values()->get(4));
        $this->assertCount(2, $frame->values()->get(4)->original());
        $this->assertSame($firstArgument, $frame->values()->get(4)->original()->get('foo'));
        $this->assertSame($secondArgument, $frame->values()->get(4)->original()->get('bar'));

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            (new Consume('queue'))->withConsumerTag('tag')
        );

        $this->assertSame('tag', (string) $frame->values()->get(2)->original());

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            (new Consume('queue'))->noLocal()
        );

        $this->assertSame(
            [true, false, false, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            (new Consume('queue'))->autoAcknowledge()
        );

        $this->assertSame(
            [false, true, false, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            (new Consume('queue'))->exclusive()
        );

        $this->assertSame(
            [false, false, true, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            (new Consume('queue'))
                ->withConsumerTag('foo')
                ->dontWait()
        );

        $this->assertSame(
            [false, false, false, true],
            $frame->values()->get(3)->original()->toPrimitive()
        );
    }

    public function testGet()
    {
        $frame = $this->basic->get(
            $channel = new Channel(1),
            new Get('queue')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(60, 70)));
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

        $frame = $this->basic->get(
            $channel = new Channel(1),
            (new Get('queue'))->autoAcknowledge()
        );

        $this->assertTrue($frame->values()->get(2)->original()->first());
    }

    public function testPublish()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            new Publish(new Generic(new Str('foobar'))),
            new MaxFrameSize(0)
        );

        $this->assertInstanceOf(StreamInterface::class, $frames);
        $this->assertSame(Frame::class, (string) $frames->type());
        $this->assertCount(3, $frames);

        $frame = $frames->first();
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(60, 40)));
        $this->assertCount(4, $frame->values());
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
        $this->assertSame(
            [false, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );

        $frame = $frames->get(1);
        $this->assertSame(Type::header(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(6, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(1)
        );
        $this->assertSame(0, $frame->values()->get(1)->original()->value());

        $frame = $frames->last();
        $this->assertSame(Type::body(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertSame(
            'foobar',
            (string) $frame->values()->first()->original()
        );
    }

    public function testPublishWithChunkedMessage()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            new Publish(new Generic(new Str('foobar'))),
            new MaxFrameSize(11)
        );

        $this->assertInstanceOf(StreamInterface::class, $frames);
        $this->assertSame(Frame::class, (string) $frames->type());
        $this->assertCount(4, $frames);

        $frame = $frames->get(1);
        $this->assertSame(6, $frame->values()->get(0)->original()->value()); //message length

        $frame = $frames->get(2);
        $this->assertSame(Type::body(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertSame(
            'foo',
            (string) $frame->values()->first()->original()
        );

        $frame = $frames->last();
        $this->assertSame(Type::body(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertSame(
            'bar',
            (string) $frame->values()->first()->original()
        );
    }

    public function testPublishWithProperties()
    {
        $basic = new Basic(new ValueTranslator);

        $frames = $basic->publish(
            $channel = new Channel(1),
            new Publish(
                (new Generic(new Str('foobar')))
                    ->withContentType(new ContentType('application', 'json'))
                    ->withContentEncoding(new ContentEncoding('gzip'))
                    ->withHeaders(
                        (new Map('string', 'mixed'))
                            ->put('foo', new ShortString(new Str('bar')))
                    )
                    ->withDeliveryMode(DeliveryMode::persistent())
                    ->withPriority(new Priority(5))
                    ->withCorrelationId(new CorrelationId('correlation'))
                    ->withReplyTo(new ReplyTo('reply'))
                    ->withExpiration(new ElapsedPeriod(1000))
                    ->withId(new Id('id'))
                    ->withTimestamp($now = new Now)
                    ->withType(new MessageType('type'))
                    ->withUserId(new UserId('guest'))
                    ->withAppId(new AppId('webcrawler'))
            ),
            new MaxFrameSize(0)
        );

        $this->assertInstanceOf(StreamInterface::class, $frames);
        $this->assertSame(Frame::class, (string) $frames->type());
        $this->assertCount(3, $frames);

        $frame = $frames->get(1);
        $this->assertSame(Type::header(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertCount(15, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(6, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(1)
        );
        $bits = 0;
        $bits |= 1 << 15;
        $bits |= 1 << 14;
        $bits |= 1 << 13;
        $bits |= 1 << 12;
        $bits |= 1 << 11;
        $bits |= 1 << 10;
        $bits |= 1 << 9;
        $bits |= 1 << 8;
        $bits |= 1 << 7;
        $bits |= 1 << 6;
        $bits |= 1 << 5;
        $bits |= 1 << 4;
        $bits |= 1 << 3;
        $this->assertSame($bits, $frame->values()->get(1)->original()->value());
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(2)
        );
        $this->assertSame(
            'application/json',
            (string) $frame->values()->get(2)->original()
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(3)
        );
        $this->assertSame(
            'gzip',
            (string) $frame->values()->get(3)->original()
        );
        $this->assertInstanceOf(
            Table::class,
            $frame->values()->get(4)
        );
        $this->assertCount(1, $frame->values()->get(4)->original());
        $this->assertSame(
            'bar',
            (string) $frame->values()->get(4)->original()->get('foo')->original()
        );
        $this->assertInstanceOf(
            UnsignedOctet::class,
            $frame->values()->get(5)
        );
        $this->assertSame(2, $frame->values()->get(5)->original()->value());
        $this->assertInstanceOf(
            UnsignedOctet::class,
            $frame->values()->get(6)
        );
        $this->assertSame(5, $frame->values()->get(6)->original()->value());
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(7)
        );
        $this->assertSame(
            'correlation',
            (string) $frame->values()->get(7)->original()
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(8)
        );
        $this->assertSame(
            'reply',
            (string) $frame->values()->get(8)->original()
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(9)
        );
        $this->assertSame(
            '1000',
            (string) $frame->values()->get(9)->original()
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(10)
        );
        $this->assertSame(
            'id',
            (string) $frame->values()->get(10)->original()
        );
        $this->assertInstanceOf(
            Timestamp::class,
            $frame->values()->get(11)
        );
        $this->assertSame($now, $frame->values()->get(11)->original());
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(12)
        );
        $this->assertSame(
            'type',
            (string) $frame->values()->get(12)->original()
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(13)
        );
        $this->assertSame(
            'guest',
            (string) $frame->values()->get(13)->original()
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(14)
        );
        $this->assertSame(
            'webcrawler',
            (string) $frame->values()->get(14)->original()
        );
    }

    public function testPublishTo()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            (new Publish(new Generic(new Str(''))))->to('foo'),
            new MaxFrameSize(0)
        );

        $frame = $frames->first();
        $this->assertSame('foo', (string) $frame->values()->get(1)->original());
    }

    public function testPublishWithRoutingKey()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            (new Publish(new Generic(new Str(''))))->withRoutingKey('foo'),
            new MaxFrameSize(0)
        );

        $frame = $frames->first();
        $this->assertSame('foo', (string) $frame->values()->get(2)->original());
    }

    public function testMandatoryPublish()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            (new Publish(new Generic(new Str(''))))->flagAsMandatory(),
            new MaxFrameSize(0)
        );

        $frame = $frames->first();
        $this->assertSame(
            [true, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );
    }

    public function testImmediatePublish()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            (new Publish(new Generic(new Str(''))))->flagAsImmediate(),
            new MaxFrameSize(0)
        );

        $frame = $frames->first();
        $this->assertSame(
            [false, true],
            $frame->values()->get(3)->original()->toPrimitive()
        );
    }

    public function testQos()
    {
        $frame = $this->basic->qos(
            $channel = new Channel(1),
            new Qos(1, 2)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(60, 10)));
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

        $frame = $this->basic->qos(
            $channel = new Channel(1),
            Qos::global(1, 2)
        );

        $this->asserttrue($frame->values()->get(2)->original()->first());
    }

    public function testRecover()
    {
        $frame = $this->basic->recover(
            $channel = new Channel(1),
            new Recover
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(60, 110)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(0));
        $this->assertFalse($frame->values()->get(0)->original()->first());

        $frame = $this->basic->recover(
            $channel = new Channel(1),
            Recover::requeue()
        );

        $this->assertTrue($frame->values()->get(0)->original()->first());
    }

    public function testReject()
    {
        $frame = $this->basic->reject(
            $channel = new Channel(1),
            new Reject(42)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(new Method(60, 90)));
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(42, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(1));
        $this->assertFalse($frame->values()->get(1)->original()->first());

        $frame = $this->basic->reject(
            $channel = new Channel(1),
            Reject::requeue(42)
        );

        $this->assertTrue($frame->values()->get(1)->original()->first());
    }
}
