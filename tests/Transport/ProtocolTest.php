<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport;

use Innmind\AMQP\{
    Transport\Protocol,
    Transport\Protocol\Connection,
    Transport\Protocol\Channel,
    Transport\Protocol\Exchange,
    Transport\Protocol\Queue,
    Transport\Protocol\Basic,
    Transport\Protocol\Transaction,
    Transport\Protocol\Version,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Value\ShortString,
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
    Model\Basic\Message\Type,
    Model\Basic\Message\UserId,
    Model\Connection\MaxFrameSize,
};
use Innmind\TimeContinuum\Earth\{
    ElapsedPeriod,
    PointInTime\Now,
    Clock,
};
use Innmind\IO\IO;
use Innmind\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\Immutable\{
    Str,
    Map,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class ProtocolTest extends TestCase
{
    public function testInterface()
    {
        $protocol = new Protocol(new Clock, new ArgumentTranslator);

        $this->assertInstanceOf(Version::class, $protocol->version());
        $this->assertSame("AMQP\x00\x00\x09\x01", $protocol->version()->pack()->toString());
        $this->assertInstanceOf(Connection::class, $protocol->connection());
        $this->assertInstanceOf(Channel::class, $protocol->channel());
        $this->assertInstanceOf(Exchange::class, $protocol->exchange());
        $this->assertInstanceOf(Queue::class, $protocol->queue());
        $this->assertInstanceOf(Basic::class, $protocol->basic());
        $this->assertInstanceOf(Transaction::class, $protocol->transaction());
    }

    public function testReadHeader()
    {
        $protocol = new Protocol(new Clock, new ArgumentTranslator);

        $header = $protocol
            ->basic()
            ->publish(
                new FrameChannel(1),
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
                        ->withType(Type::of('type'))
                        ->withUserId(UserId::of('guest'))
                        ->withAppId(AppId::of('webcrawler')),
                ),
                MaxFrameSize::of(10),
            )
            ->get(1)
            ->match(
                static fn($value) => $value,
                static fn() => null,
            );

        $values = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap(
                Stream::ofContent(
                    \implode(
                        '',
                        $header
                            ->values()
                            ->map(static fn($v) => $v->pack()->toString())
                            ->toList(),
                    ),
                ),
            )
            ->toEncoding(Str\Encoding::ascii)
            ->frames($protocol->headerFrame())
            ->one()
            ->match(
                static fn($values) => $values,
                static fn() => null,
            );

        $this->assertInstanceOf(Sequence::class, $values);
        $this->assertCount(15, $values); // body size + flag bits + 13 properties
        $this->assertSame(
            Str::of('')
                ->join(
                    $values->map(
                        static fn($v) => $v->pack()->toString(),
                    ),
                )
                ->toString(),
            Str::of('')
                ->join(
                    $header->values()->map(
                        static fn($v) => $v->pack()->toString(),
                    ),
                )
                ->toString(),
        );
    }
}
