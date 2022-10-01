<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection\FrameReader,
    Transport\Frame,
    Transport\Frame\Type,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\Text,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\Timestamp,
    Transport\Protocol\v091\Protocol,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Model\Basic\Publish,
    Model\Basic\Message\Generic,
    Model\Basic\Message\AppId,
    Model\Basic\Message\ContentEncoding,
    Model\Basic\Message\ContentType,
    Model\Basic\Message\CorrelationId,
    Model\Basic\Message\DeliveryMode,
    Model\Basic\Message\Id,
    Model\Basic\Message\Priority,
    Model\Basic\Message\ReplyTo,
    Model\Basic\Message\Type as MessageType,
    Model\Basic\Message\UserId,
    Model\Connection\MaxFrameSize,
    TimeContinuum\Format\Timestamp as TimestampFormat,
    Exception\NoFrameDetected,
    Exception\ReceivedFrameNotDelimitedCorrectly,
    Exception\PayloadTooShort,
};
use Innmind\Stream\{
    Readable\Stream,
    Readable,
};
use Innmind\Math\Algebra\Integer;
use Innmind\TimeContinuum\Earth\{
    ElapsedPeriod,
    PointInTime\Now,
};
use Innmind\Immutable\{
    Str,
    Map,
};
use PHPUnit\Framework\TestCase;

class FrameReaderTest extends TestCase
{
    private $protocol;

    public function setUp(): void
    {
        $this->protocol = new Protocol(new ValueTranslator);
    }

    public function testReadCommand()
    {
        $read = new FrameReader;

        $file = \tmpfile();
        \fwrite(
            $file,
            Frame::method(
                new Channel(0),
                new Method(10, 10), // connection.start
                new UnsignedOctet(new Integer(0)),
                new UnsignedOctet(new Integer(9)),
                new Table(Map::of('string', Value::class)),
                new LongString(Str::of('AMQPLAIN')),
                new LongString(Str::of('en_US')),
            )->toString(),
        );
        \fseek($file, 0);
        $stream = new Stream($file);

        $frame = $read($stream, $this->protocol);

        $this->assertInstanceOf(Frame::class, $frame);
    }

    public function testThrowWhenFrameEndMarkerInvalid()
    {
        $read = new FrameReader;

        $file = \tmpfile();
        $frame = Frame::method(
            new Channel(0),
            new Method(10, 10), // connection.start
            new UnsignedOctet(new Integer(0)),
            new UnsignedOctet(new Integer(9)),
            new Table(Map::of('string', Value::class)),
            new LongString(Str::of('AMQPLAIN')),
            new LongString(Str::of('en_US')),
        )->toString();
        $frame = \mb_substr($frame, 0, -1, 'ASCII'); //remove end marker
        $frame .= (new UnsignedOctet(new Integer(0xCD)))->pack();
        \fwrite($file, $frame);
        \fseek($file, 0);
        $stream = new Stream($file);

        $this->expectException(ReceivedFrameNotDelimitedCorrectly::class);

        $read($stream, $this->protocol);
    }

    public function testThrowWhenPayloadTooShort()
    {
        $read = new FrameReader;

        $file = \tmpfile();
        $frame = Frame::method(
            new Channel(0),
            new Method(10, 10), // connection.start
        )->toString();
        $frame = \mb_substr($frame, 0, -2, 'ASCII');
        \fwrite($file, $frame);
        \fseek($file, 0);
        $stream = new Stream($file);

        $this->expectException(PayloadTooShort::class);

        $read($stream, $this->protocol);
    }

    public function testThrowWhenNoFrameDeteted()
    {
        $file = \tmpfile();
        \fwrite($file, $content = "AMQP\x00\x00\x09\x01");
        \fseek($file, 0);
        $stream = new Stream($file);

        try {
            (new FrameReader)($stream, $this->protocol);
            $this->fail('it should throw an exception');
        } catch (NoFrameDetected $e) {
            $this->assertInstanceOf(Readable::class, $e->content());
            $this->assertSame($content, $e->content()->toString());
        }
    }

    public function testReadHeader()
    {
        $header = $this
            ->protocol
            ->basic()
            ->publish(
                new Channel(1),
                new Publish(
                    (new Generic(Str::of('foobar')))
                        ->withContentType(new ContentType('application', 'json'))
                        ->withContentEncoding(new ContentEncoding('gzip'))
                        ->withHeaders(
                            Map::of('string', 'mixed')
                                ('foo', new ShortString(Str::of('bar'))),
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
                        ->withAppId(new AppId('webcrawler')),
                ),
                new MaxFrameSize(10),
            )
            ->get(1);
        $file = \tmpfile();
        \fwrite($file, $header->toString());
        \fseek($file, 0);

        $frame = (new FrameReader)(new Stream($file), $this->protocol);

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::header(), $frame->type());
        $this->assertSame(1, $frame->channel()->toInt());
        $this->assertCount(15, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->first(),
        );
        $this->assertSame(6, $frame->values()->first()->original()->value()); //body size
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(1),
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
            $frame->values()->get(2),
        );
        $this->assertSame(
            'application/json',
            $frame->values()->get(2)->original()->toString(),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(3),
        );
        $this->assertSame(
            'gzip',
            $frame->values()->get(3)->original()->toString(),
        );
        $this->assertInstanceOf(
            Table::class,
            $frame->values()->get(4),
        );
        $this->assertCount(1, $frame->values()->get(4)->original());
        $this->assertSame(
            'bar',
            $frame->values()->get(4)->original()->get('foo')->original()->toString(),
        );
        $this->assertInstanceOf(
            UnsignedOctet::class,
            $frame->values()->get(5),
        );
        $this->assertSame(
            2,
            $frame->values()->get(5)->original()->value(),
        );
        $this->assertInstanceOf(
            UnsignedOctet::class,
            $frame->values()->get(6),
        );
        $this->assertSame(
            5,
            $frame->values()->get(6)->original()->value(),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(7),
        );
        $this->assertSame(
            'correlation',
            $frame->values()->get(7)->original()->toString(),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(8),
        );
        $this->assertSame(
            'reply',
            $frame->values()->get(8)->original()->toString(),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(9),
        );
        $this->assertSame(
            '1000',
            $frame->values()->get(9)->original()->toString(),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(10),
        );
        $this->assertSame(
            'id',
            $frame->values()->get(10)->original()->toString(),
        );
        $this->assertInstanceOf(
            Timestamp::class,
            $frame->values()->get(11),
        );
        $this->assertSame(
            $now->format(new TimestampFormat),
            $frame->values()->get(11)->original()->format(new TimestampFormat),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(12),
        );
        $this->assertSame(
            'type',
            $frame->values()->get(12)->original()->toString(),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(13),
        );
        $this->assertSame(
            'guest',
            $frame->values()->get(13)->original()->toString(),
        );
        $this->assertInstanceOf(
            ShortString::class,
            $frame->values()->get(14),
        );
        $this->assertSame(
            'webcrawler',
            $frame->values()->get(14)->original()->toString(),
        );
    }

    public function testReadBody()
    {
        $file = \tmpfile();
        \fwrite($file, Frame::body(
            new Channel(1),
            Str::of('foobar'),
        )->toString());
        \fseek($file, 0);

        $frame = (new FrameReader)(new Stream($file), $this->protocol);

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::body(), $frame->type());
        $this->assertSame(1, $frame->channel()->toInt());
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Text::class, $frame->values()->first());
        $this->assertSame('foobar', $frame->values()->first()->original()->toString());
    }

    public function testReadHeartbeat()
    {
        $file = \tmpfile();
        \fwrite($file, Frame::heartbeat()->toString());
        \fseek($file, 0);

        $frame = (new FrameReader)(new Stream($file), $this->protocol);

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::heartbeat(), $frame->type());
    }
}
