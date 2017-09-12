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
    Exception\NoFrameDetected
};
use Innmind\Stream\Readable\Stream;
use Innmind\Math\Algebra\Integer;
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    PointInTime\Earth\Now
};
use Innmind\Immutable\{
    Str,
    Map
};
use PHPUnit\Framework\TestCase;

class FrameReaderTest extends TestCase
{
    private $protocol;

    public function setUp()
    {
        $this->protocol = new Protocol(new ValueTranslator);
    }

    public function testReadCommand()
    {
        $read = new FrameReader;

        $file = tmpfile();
        fwrite(
            $file,
            (string) Frame::method(
                new Channel(0),
                new Method(10, 10), // connection.start
                new UnsignedOctet(new Integer(0)),
                new UnsignedOctet(new Integer(9)),
                new Table(new Map('string', Value::class)),
                new LongString(new Str('AMQPLAIN')),
                new LongString(new Str('en_US'))
            )
        );
        fseek($file, 0);
        $stream = new Stream($file);

        $frame = $read($stream, $this->protocol);

        $this->assertInstanceOf(Frame::class, $frame);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\ReceivedFrameNotDelimitedCorrectly
     */
    public function testThrowWhenFrameMissingEndMarker()
    {
        $read = new FrameReader;

        $file = tmpfile();
        $frame = (string) Frame::method(
            new Channel(0),
            new Method(10, 10), // connection.start
            new UnsignedOctet(new Integer(0)),
            new UnsignedOctet(new Integer(9)),
            new Table(new Map('string', Value::class)),
            new LongString(new Str('AMQPLAIN')),
            new LongString(new Str('en_US'))
        );
        $frame = mb_substr($frame, 0, -4, 'ASCII'); //remove end marker
        fwrite($file, $frame);
        fseek($file, 0);
        $stream = new Stream($file);

        $read($stream, $this->protocol);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\ReceivedFrameNotDelimitedCorrectly
     */
    public function testThrowWhenFrameEndMarkerInvalid()
    {
        $read = new FrameReader;

        $file = tmpfile();
        $frame = (string) Frame::method(
            new Channel(0),
            new Method(10, 10), // connection.start
            new UnsignedOctet(new Integer(0)),
            new UnsignedOctet(new Integer(9)),
            new Table(new Map('string', Value::class)),
            new LongString(new Str('AMQPLAIN')),
            new LongString(new Str('en_US'))
        );
        $frame = mb_substr($frame, 0, -4, 'ASCII'); //remove end marker
        $frame .= new UnsignedOctet(new Integer(0xCD));
        fwrite($file, $frame);
        fseek($file, 0);
        $stream = new Stream($file);

        $read($stream, $this->protocol);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\PayloadTooShort
     */
    public function testThrowWhenPayloadTooShort()
    {
        $read = new FrameReader;

        $file = tmpfile();
        $frame = (string) Frame::method(
            new Channel(0),
            new Method(10, 10) // connection.start
        );
        $frame = mb_substr($frame, 0, -2, 'ASCII');
        fwrite($file, $frame);
        fseek($file, 0);
        $stream = new Stream($file);

        $read($stream, $this->protocol);
    }

    public function testThrowWhenNoFrameDeteted()
    {
        $file = tmpfile();
        fwrite($file, $content = "AMQP\x00\x00\x09\x01");
        fseek($file, 0);
        $stream = new Stream($file);

        try {
            (new FrameReader)($stream, $this->protocol);
            $this->fail('it should throw an exception');
        } catch (NoFrameDetected $e) {
            $this->assertInstanceOf(Str::class, $e->content());
            $this->assertSame($content, (string) $e->content());
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
                new MaxFrameSize(10)
            )
            ->get(1);
        $file = tmpfile();
        fwrite($file, (string) $header);
        fseek($file, 0);

        $frame = (new FrameReader)(new Stream($file), $this->protocol);

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::header(), $frame->type());
        $this->assertSame(1, $frame->channel()->toInt());
        $this->assertCount(15, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->first()
        );
        $this->assertSame(6, $frame->values()->first()->original()->value()); //body size
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
        $this->assertSame(
            2,
            $frame->values()->get(5)->original()->value()
        );
        $this->assertInstanceOf(
            UnsignedOctet::class,
            $frame->values()->get(6)
        );
        $this->assertSame(
            5,
            $frame->values()->get(6)->original()->value()
        );
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
        $this->assertSame(
            $now->format(new TimestampFormat),
            $frame->values()->get(11)->original()->format(new TimestampFormat)
        );
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

    public function testReadBody()
    {
        $file = tmpfile();
        fwrite($file, (string) Frame::body(
            new Channel(1),
            new Str('foobar')
        ));
        fseek($file, 0);

        $frame = (new FrameReader)(new Stream($file), $this->protocol);

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::body(), $frame->type());
        $this->assertSame(1, $frame->channel()->toInt());
        $this->assertCount(1, $frame->values());
        $this->assertInstanceOf(Text::class, $frame->values()->first());
        $this->assertSame('foobar', (string) $frame->values()->first()->original());
    }

    public function testReadHeartbeat()
    {
        $file = tmpfile();
        fwrite($file, (string) Frame::heartbeat());
        fseek($file, 0);

        $frame = (new FrameReader)(new Stream($file), $this->protocol);

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::heartbeat(), $frame->type());
    }
}
