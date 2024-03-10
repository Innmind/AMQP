<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection\FrameReader,
    Transport\Frame,
    Transport\Frame\Type,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\Timestamp,
    Transport\Protocol,
    Transport\Protocol\ArgumentTranslator,
    Model\Basic\Publish,
    Model\Basic\Message,
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
};
use Innmind\IO\IO;
use Innmind\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\TimeContinuum\Earth\{
    ElapsedPeriod,
    PointInTime\Now,
    Clock,
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
        $this->protocol = new Protocol(new Clock, new ArgumentTranslator);
    }

    public function testReadCommand()
    {
        $file = \tmpfile();
        \fwrite(
            $file,
            Frame::method(
                new Channel(0),
                Method::of(10, 10), // connection.start
                UnsignedOctet::of(0),
                UnsignedOctet::of(9),
                Table::of(Map::of()),
                LongString::literal('AMQPLAIN'),
                LongString::literal('en_US'),
            )->pack()->toString(),
        );
        \fseek($file, 0);

        $frame = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::of($file))
            ->toEncoding(Str\Encoding::ascii)
            ->frames((new FrameReader)($this->protocol))
            ->one()
            ->match(
                static fn($frame) => $frame,
                static fn() => null,
            );

        $this->assertInstanceOf(Frame::class, $frame);
    }

    public function testReturnNothingWhenFrameEndMarkerInvalid()
    {
        $file = \tmpfile();
        $frame = Frame::method(
            new Channel(0),
            Method::of(10, 10), // connection.start
            UnsignedOctet::of(0),
            UnsignedOctet::of(9),
            Table::of(Map::of()),
            LongString::literal('AMQPLAIN'),
            LongString::literal('en_US'),
        )->pack()->toString();
        $frame = \mb_substr($frame, 0, -1, 'ASCII'); //remove end marker
        $frame .= (UnsignedOctet::of(0xCD))->pack()->toString();
        \fwrite($file, $frame);
        \fseek($file, 0);

        $frame = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::of($file))
            ->toEncoding(Str\Encoding::ascii)
            ->frames((new FrameReader)($this->protocol))
            ->one()
            ->match(
                static fn($frame) => $frame,
                static fn() => null,
            );

        $this->assertNull($frame);
    }

    public function testReturnNothingWhenPayloadTooShort()
    {
        $file = \tmpfile();
        $frame = Frame::method(
            new Channel(0),
            Method::of(10, 10), // connection.start
        )->pack()->toString();
        $frame = \mb_substr($frame, 0, -2, 'ASCII');
        \fwrite($file, $frame);
        \fseek($file, 0);

        $frame = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::of($file))
            ->toEncoding(Str\Encoding::ascii)
            ->frames((new FrameReader)($this->protocol))
            ->one()
            ->match(
                static fn($frame) => $frame,
                static fn() => null,
            );

        $this->assertNull($frame);
    }

    public function testReturnNothingWhenNoFrameDeteted()
    {
        $file = \tmpfile();
        \fwrite($file, $content = "AMQP\x00\x00\x09\x01");
        \fseek($file, 0);

        $frame = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::of($file))
            ->toEncoding(Str\Encoding::ascii)
            ->frames((new FrameReader)($this->protocol))
            ->one()
            ->match(
                static fn($frame) => $frame,
                static fn() => null,
            );

        $this->assertNull($frame);
    }

    public function testReadHeader()
    {
        $header = $this
            ->protocol
            ->basic()
            ->publish(
                new Channel(1),
                Publish::a(
                    Message::of(Str::of('foobar'))
                        ->withContentType(ContentType::of('application', 'json'))
                        ->withContentEncoding(ContentEncoding::of('gzip'))
                        ->withHeaders(
                            Map::of(['foo', ShortString::of(Str::of('bar'))]),
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
                MaxFrameSize::of(10),
            )
            ->get(1)
            ->match(
                static fn($header) => $header,
                static fn() => null,
            );
        $file = \tmpfile();
        \fwrite($file, $header->pack()->toString());
        \fseek($file, 0);

        $frame = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::of($file))
            ->toEncoding(Str\Encoding::ascii)
            ->frames((new FrameReader)($this->protocol))
            ->one()
            ->match(
                static fn($frame) => $frame,
                static fn() => null,
            );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::header, $frame->type());
        $this->assertSame(1, $frame->channel()->toInt());
        $this->assertCount(15, $frame->values());
        $this->assertInstanceOf(
            UnsignedLongLongInteger::class,
            $frame->values()->first()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(6, $frame->values()->first()->match(
            static fn($value) => $value->original(),
            static fn() => null,
        )); //body size
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
        $this->assertSame(
            2,
            $frame->values()->get(5)->match(
                static fn($value) => $value->original(),
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            UnsignedOctet::class,
            $frame->values()->get(6)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            5,
            $frame->values()->get(6)->match(
                static fn($value) => $value->original(),
                static fn() => null,
            ),
        );
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
        $this->assertSame(
            $now->format(new TimestampFormat),
            $frame->values()->get(11)->match(
                static fn($value) => $value->original()->format(new TimestampFormat),
                static fn() => null,
            ),
        );
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

    public function testReadBody()
    {
        $file = \tmpfile();
        \fwrite($file, Frame::body(
            new Channel(1),
            Str::of('foobar'),
        )->pack()->toString());
        \fseek($file, 0);

        $frame = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::of($file))
            ->toEncoding(Str\Encoding::ascii)
            ->frames((new FrameReader)($this->protocol))
            ->one()
            ->match(
                static fn($frame) => $frame,
                static fn() => null,
            );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::body, $frame->type());
        $this->assertSame(1, $frame->channel()->toInt());
        $this->assertCount(0, $frame->values());
        $this->assertSame('foobar', $frame->content()->match(
            static fn($value) => $value->toString(),
            static fn() => null,
        ));
    }

    public function testReadHeartbeat()
    {
        $file = \tmpfile();
        \fwrite($file, Frame::heartbeat()->pack()->toString());
        \fseek($file, 0);

        $frame = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(Stream::of($file))
            ->toEncoding(Str\Encoding::ascii)
            ->frames((new FrameReader)($this->protocol))
            ->one()
            ->match(
                static fn($frame) => $frame,
                static fn() => null,
            );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::heartbeat, $frame->type());
    }
}
