<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Protocol\Basic,
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
use Innmind\TimeContinuum\Earth\{
    PointInTime\Now,
    ElapsedPeriod,
};
use Innmind\Immutable\{
    Str,
    Sequence,
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
            $this->translator = $this->createMock(ArgumentTranslator::class),
        );
    }

    public function testAck()
    {
        $frame = $this->basic->ack(
            $channel = new Channel(1),
            Ack::of(42),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(60, 80)));
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(42, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertFalse($frame->values()->get(1)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = $this->basic->ack(
            $channel = new Channel(1),
            Ack::multiple(42),
        );

        $this->assertTrue($frame->values()->get(1)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }

    public function testCancel()
    {
        $frame = $this->basic->cancel(
            $channel = new Channel(1),
            Cancel::of('consumer'),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(60, 30)));
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(0)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('consumer', $frame->values()->get(0)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertFalse($frame->values()->get(1)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = $this->basic->cancel(
            $channel = new Channel(1),
            Cancel::of('consumer')->dontWait(),
        );

        $this->assertTrue($frame->values()->get(1)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }

    public function testConsume()
    {
        $this
            ->translator
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive([24], [42])
            ->will($this->onConsecutiveCalls(
                $firstArgument = UnsignedShortInteger::of(24),
                $secondArgument = UnsignedShortInteger::of(42),
            ));
        $frame = $this->basic->consume(
            $channel = new Channel(1),
            Consume::of('queue')
                ->withArgument('foo', 24)
                ->withArgument('bar', 42),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(60, 20)));
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
        $this->assertSame('queue', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('', $frame->values()->get(2)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(3)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(
            [false, false, false, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(Table::class, $frame->values()->get(4)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertCount(2, $frame->values()->get(4)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertSame($firstArgument, $frame->values()->get(4)->match(
            static fn($value) => $value->original()->get('foo')->match(
                static fn($argument) => $argument,
                static fn() => null,
            ),
            static fn() => null,
        ));
        $this->assertSame($secondArgument, $frame->values()->get(4)->match(
            static fn($value) => $value->original()->get('bar')->match(
                static fn($argument) => $argument,
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            Consume::of('queue')->withConsumerTag('tag'),
        );

        $this->assertSame('tag', $frame->values()->get(2)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            Consume::of('queue')->noLocal(),
        );

        $this->assertSame(
            [true, false, false, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            Consume::of('queue')->autoAcknowledge(),
        );

        $this->assertSame(
            [false, true, false, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            Consume::of('queue')->exclusive(),
        );

        $this->assertSame(
            [false, false, true, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $this->basic->consume(
            $channel = new Channel(1),
            Consume::of('queue')
                ->withConsumerTag('foo')
                ->dontWait(),
        );

        $this->assertSame(
            [false, false, false, true],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );
    }

    public function testGet()
    {
        $frame = $this->basic->get(
            $channel = new Channel(1),
            Get::of('queue'),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(60, 70)));
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
        $this->assertSame('queue', $frame->values()->get(1)->match(
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

        $frame = $this->basic->get(
            $channel = new Channel(1),
            Get::of('queue')->autoAcknowledge(),
        );

        $this->assertTrue($frame->values()->get(2)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }

    public function testPublish()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            Publish::a(Generic::of(Str::of('foobar'))),
            MaxFrameSize::of(0),
        );

        $this->assertInstanceOf(Sequence::class, $frames);
        $this->assertCount(3, $frames);

        $frame = $frames->first()->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(60, 40)));
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
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('', $frame->values()->get(2)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(3)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame(
            [false, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );

        $frame = $frames->get(1)->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame(Type::header, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(6, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(1)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(0, $frame->values()->get(1)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));

        $frame = $frames->last()->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame(Type::body, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertSame(
            'foobar',
            $frame->content()->match(
                static fn($value) => $value->toString(),
                static fn() => null,
            ),
        );
    }

    public function testPublishWithChunkedMessage()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            Publish::a(Generic::of(Str::of('foobar'))),
            MaxFrameSize::of(11),
        );

        $this->assertInstanceOf(Sequence::class, $frames);
        $this->assertCount(4, $frames);

        $frame = $frames->get(1)->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame(6, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        )); //message length

        $frame = $frames->get(2)->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame(Type::body, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertSame(
            'foo',
            $frame->content()->match(
                static fn($value) => $value->toString(),
                static fn() => null,
            ),
        );

        $frame = $frames->last()->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame(Type::body, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertSame(
            'bar',
            $frame->content()->match(
                static fn($value) => $value->toString(),
                static fn() => null,
            ),
        );
    }

    public function testPublishWithProperties()
    {
        $basic = new Basic(new ValueTranslator);

        $frames = $basic->publish(
            $channel = new Channel(1),
            Publish::a(
                Generic::of(Str::of('foobar'))
                    ->withContentType(ContentType::of('application', 'json'))
                    ->withContentEncoding(ContentEncoding::of('gzip'))
                    ->withHeaders(
                        Map::of(['foo', ShortString::literal('bar')]),
                    )
                    ->withDeliveryMode(DeliveryMode::persistent)
                    ->withPriority(Priority::five)
                    ->withCorrelationId(CorrelationId::of('correlation'))
                    ->withReplyTo(ReplyTo::of('reply'))
                    ->withExpiration(new ElapsedPeriod(1000))
                    ->withId(Id::of('id'))
                    ->withTimestamp($now = new Now)
                    ->withType(MessageType::of('type'))
                    ->withUserId(UserId::of('guest'))
                    ->withAppId(AppId::of('webcrawler')),
            ),
            MaxFrameSize::of(0),
        );

        $this->assertInstanceOf(Sequence::class, $frames);
        $this->assertCount(3, $frames);

        $frame = $frames->get(1)->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame(Type::header, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertCount(15, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(6, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(1)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
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
        $this->assertSame($bits, $frame->values()->get(1)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(2)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            'application/json',
            $frame->values()->get(2)->match(
                static fn($value) => $value->original()->toString(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(3)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            'gzip',
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toString(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Table::class,
            $frame->values()->get(4)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertCount(1, $frame->values()->get(4)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertSame(
            'bar',
            $frame
                ->values()
                ->get(4)
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                )
                ->original()
                ->get('foo')
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                )
                ->original()
                ->toString(),
        );
        $this->assertInstanceOf(
            UnsignedOctet::class,
            $frame->values()->get(5)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(2, $frame->values()->get(5)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            UnsignedOctet::class,
            $frame->values()->get(6)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(5, $frame->values()->get(6)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(7)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            'correlation',
            $frame->values()->get(7)->match(
                static fn($value) => $value->original()->toString(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(8)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            'reply',
            $frame->values()->get(8)->match(
                static fn($value) => $value->original()->toString(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(9)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            '1000',
            $frame->values()->get(9)->match(
                static fn($value) => $value->original()->toString(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(10)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            'id',
            $frame->values()->get(10)->match(
                static fn($value) => $value->original()->toString(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Timestamp::class,
            $frame->values()->get(11)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame($now, $frame->values()->get(11)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(12)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            'type',
            $frame->values()->get(12)->match(
                static fn($value) => $value->original()->toString(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(13)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            'guest',
            $frame->values()->get(13)->match(
                static fn($value) => $value->original()->toString(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(14)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            'webcrawler',
            $frame->values()->get(14)->match(
                static fn($value) => $value->original()->toString(),
                static fn() => null,
            ),
        );
    }

    public function testPublishTo()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            Publish::a(Generic::of(Str::of('')))->to('foo'),
            MaxFrameSize::of(0),
        );

        $frame = $frames->first()->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame('foo', $frame->values()->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
    }

    public function testPublishWithRoutingKey()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            Publish::a(Generic::of(Str::of('')))->withRoutingKey('foo'),
            MaxFrameSize::of(0),
        );

        $frame = $frames->first()->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame('foo', $frame->values()->get(2)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
    }

    public function testMandatoryPublish()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            Publish::a(Generic::of(Str::of('')))->flagAsMandatory(),
            MaxFrameSize::of(0),
        );

        $frame = $frames->first()->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame(
            [true, false],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );
    }

    public function testImmediatePublish()
    {
        $frames = $this->basic->publish(
            $channel = new Channel(1),
            Publish::a(Generic::of(Str::of('')))->flagAsImmediate(),
            MaxFrameSize::of(0),
        );

        $frame = $frames->first()->match(
            static fn($frame) => $frame,
            static fn() => null,
        );
        $this->assertSame(
            [false, true],
            $frame->values()->get(3)->match(
                static fn($value) => $value->original()->toList(),
                static fn() => null,
            ),
        );
    }

    public function testQos()
    {
        $frame = $this->basic->qos(
            $channel = new Channel(1),
            Qos::of(1, 2),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(60, 10)));
        $this->assertCount(3, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongInteger::class,
            $frame->values()->get(0)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(1, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(1)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(2, $frame->values()->get(1)->match(
            static fn($value) => $value->original(),
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

        $frame = $this->basic->qos(
            $channel = new Channel(1),
            Qos::global(1, 2),
        );

        $this->asserttrue($frame->values()->get(2)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }

    public function testRecover()
    {
        $frame = $this->basic->recover(
            $channel = new Channel(1),
            Recover::withoutRequeue(),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(60, 110)));
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(0)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertFalse($frame->values()->get(0)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = $this->basic->recover(
            $channel = new Channel(1),
            Recover::requeue(),
        );

        $this->assertTrue($frame->values()->get(0)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }

    public function testReject()
    {
        $frame = $this->basic->reject(
            $channel = new Channel(1),
            Reject::of(42),
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(60, 90)));
        $this->assertCount(2, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->get(0)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(42, $frame->values()->get(0)->match(
            static fn($value) => $value->original(),
            static fn() => null,
        ));
        $this->assertInstanceOf(Bits::class, $frame->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertFalse($frame->values()->get(1)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));

        $frame = $this->basic->reject(
            $channel = new Channel(1),
            Reject::requeue(42),
        );

        $this->assertTrue($frame->values()->get(1)->match(
            static fn($value) => $value->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => null,
            ),
            static fn() => null,
        ));
    }
}
