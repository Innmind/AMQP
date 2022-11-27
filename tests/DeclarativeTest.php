<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP;

use Innmind\AMQP\{
    Declarative,
    Transport\Connection,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Transport\Protocol,
    Transport\Frame\Value,
    Command\DeclareExchange,
    Command\DeleteExchange,
    Command\DeclareQueue,
    Command\DeleteQueue,
    Command\Bind,
    Command\Unbind,
    Command\Purge,
    Command\Qos,
    Command\Publish,
    Command\Get,
    Command\Consume,
    Model\Exchange\Type,
    Model\Basic,
    TimeContinuum\Format\Timestamp as TimestampFormat,
    Exception\BasicGetNotCancellable,
};
use Innmind\Socket\Internet\Transport;
use Innmind\OperatingSystem\Factory;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Url\Url;
use Innmind\Immutable\{
    Str,
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class DeclarativeTest extends TestCase
{
    private Declarative $client;
    private $clock;

    public function setUp(): void
    {
        $os = Factory::build();

        $this->client = Declarative::of(
            static fn() => Connection::open(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5672/'),
                new Protocol($os->clock(), new ValueTranslator),
                new ElapsedPeriod(1000),
                $os->clock(),
                $os->remote(),
                $os->sockets(),
            ),
        );
        $this->clock = $os->clock();
    }

    public function testDeclareExchange()
    {
        $result = $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Qos::of(10))
            ->with(Publish::one(
                Basic\Publish::a(Basic\Message::of(Str::of('message')))
                    ->to('foo'),
            ))
            ->with(
                Get::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertNull($state);
                    $this->assertFalse($details->redelivered());
                    $this->assertSame('foo', $details->exchange());
                    $this->assertSame('', $details->routingKey());
                    $this->assertSame('message', $message->body()->toString());

                    return $continuation->requeue('requeued');
                }),
            )
            ->with(
                Get::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertSame('requeued', $state);
                    $this->assertTrue($details->redelivered());
                    $this->assertSame('foo', $details->exchange());
                    $this->assertSame('', $details->routingKey());
                    $this->assertSame('message', $message->body()->toString());

                    return $continuation->ack($message->body()->toString());
                }),
            )
            ->with(Get::of('bar'))
            ->with(Unbind::of('foo', 'bar'))
            ->with(DeleteQueue::of('bar'))
            ->with(DeleteExchange::of('foo'))
            ->run(null)
            ->match(
                static fn($state) => $state,
                static fn($error) => $error,
            );

        $this->assertSame('message', $result);
    }

    public function testMultipleGet()
    {
        $result = $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Publish::one(
                Basic\Publish::a(Basic\Message::of(Str::of('message0')))
                    ->to('foo'),
            ))
            ->with(Publish::one(
                Basic\Publish::a(Basic\Message::of(Str::of('message1')))
                    ->to('foo'),
            ))
            ->with(
                Get::of('bar')
                    ->take(3) // the third attempt will return a basic.get-empty and the handler is not called
                    ->handle(function($state, $message, $continuation, $details) {
                        $this->assertSame('message'.$state, $message->body()->toString());

                        return $continuation->ack($state + 1);
                    }),
            )
            ->with(Unbind::of('foo', 'bar'))
            ->with(DeleteQueue::of('bar'))
            ->with(DeleteExchange::of('foo'))
            ->run(0)
            ->match(
                static fn($state) => $state,
                static fn($error) => $error,
            );

        $this->assertSame(2, $result);
    }

    public function testCancelingAGetThrowsAnException()
    {
        $this->expectException(BasicGetNotCancellable::class);

        $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Publish::one(
                Basic\Publish::a(Basic\Message::of(Str::of('message')))
                    ->to('foo'),
            ))
            ->with(
                Get::of('bar')
                    ->take(3)
                    ->handle(function($state, $message, $continuation, $details) {
                        $this->assertSame('message', $message->body()->toString());

                        return $continuation->cancel($state);
                    }),
            )
            ->with(Unbind::of('foo', 'bar'))
            ->with(DeleteQueue::of('bar'))
            ->with(DeleteExchange::of('foo'))
            ->run(null);
    }

    public function testConsume()
    {
        $result = $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Qos::of(10))
            ->with(Publish::many(Sequence::of(
                Basic\Publish::a(Basic\Message::of(Str::of('message0')))
                    ->to('foo'),
                Basic\Publish::a(Basic\Message::of(Str::of('message1')))
                    ->to('foo'),
                Basic\Publish::a(Basic\Message::of(Str::of('message2')))
                    ->to('foo'),
                Basic\Publish::a(Basic\Message::of(Str::of('message3')))
                    ->to('foo'),
            )))
            ->with(
                Consume::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertSame('message'.$state, $message->body()->toString());

                    if ($state === 1) {
                        // we don't increment the state because the message with
                        // the content "message1" will be requeued and will be
                        // delivered in the second consumer below
                        return $continuation->cancel($state);
                    }

                    return $continuation->ack($state + 1);
                }),
            )
            // this second consume proves that the consumer above correctly
            // recovered the prefetched messages, otherwise the messages would
            // be "nowhere" and the consumer below would hang forever
            ->with(
                Consume::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertSame('message'.$state, $message->body()->toString());

                    if ($state === 3) {
                        return $continuation->cancel($state + 1);
                    }

                    return $continuation->ack($state + 1);
                }),
            )
            ->with(Unbind::of('foo', 'bar'))
            ->with(DeleteQueue::of('bar'))
            ->with(DeleteExchange::of('foo'))
            ->run(0)
            ->match(
                static fn($state) => $state,
                static fn($error) => $error,
            );

        $this->assertSame(4, $result);
    }

    public function testReject()
    {
        $result = $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Qos::of(10))
            ->with(Publish::one(
                Basic\Publish::a(Basic\Message::of(Str::of('message')))
                    ->to('foo'),
            ))
            ->with(
                Get::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertNull($state);
                    $this->assertFalse($details->redelivered());

                    return $continuation->reject('rejected');
                }),
            )
            ->with(
                Get::of('bar')->handle(function($state, $message, $continuation, $details) {
                    $this->assertFalse(true, 'The second handler should not be called');

                    return $continuation->ack($message->body()->toString());
                }),
            )
            ->with(Get::of('bar'))
            ->with(Unbind::of('foo', 'bar'))
            ->with(DeleteQueue::of('bar'))
            ->with(DeleteExchange::of('foo'))
            ->run(null)
            ->match(
                static fn($state) => $state,
                static fn($error) => $error,
            );

        $this->assertSame('rejected', $result);
    }

    public function testGetMessageWithAllProperties()
    {
        $message = Basic\Message::of(Str::of('message'))
            ->withContentType(Basic\Message\ContentType::of('text', 'plain'))
            ->withContentEncoding(Basic\Message\ContentEncoding::of('gzip'))
            ->withDeliveryMode(Basic\Message\DeliveryMode::persistent)
            ->withPriority(Basic\Message\Priority::five)
            ->withCorrelationId(Basic\Message\CorrelationId::of('correlation'))
            ->withReplyTo(Basic\Message\ReplyTo::of('reply'))
            ->withExpiration(ElapsedPeriod::of(10000))
            ->withId(Basic\Message\Id::of('id'))
            ->withTimestamp($now = $this->clock->now())
            ->withType(Basic\Message\Type::of('type'))
            ->withUserId(Basic\Message\UserId::of('guest'))
            ->withAppId(Basic\Message\AppId::of('webcrawler'))
            ->withHeaders(
                Map::of(
                    ['bits', Value\Bits::of(true)],
                    ['decimal', Value\Decimal::of(1, 1)],
                    ['longstr', Value\LongString::literal('bar')],
                    ['array', Value\Sequence::of(Value\Bits::of(true))],
                    ['long', Value\SignedLongInteger::of(2)],
                    ['octet', Value\SignedOctet::of(4)],
                    ['table', Value\Table::of(Map::of(['inner', Value\Bits::of(true)]))],
                    ['timestamp', Value\Timestamp::of($ts = $this->clock->now())],
                    ['ulong', Value\UnsignedLongInteger::of(6)],
                    ['ulonglong', Value\UnsignedLongLongInteger::of(7)],
                    ['uoctet', Value\UnsignedOctet::of(8)],
                    ['ushort', Value\UnsignedShortInteger::of(9)],
                    ['void', new Value\VoidValue],
                ),
            );
        $result = $this
            ->client
            ->with(DeclareExchange::of('foo', Type::direct))
            ->with(DeclareQueue::of('bar'))
            ->with(Bind::of('foo', 'bar'))
            ->with(Purge::of('bar'))
            ->with(Qos::of(10))
            ->with(Publish::one(
                Basic\Publish::a($message)
                    ->to('foo'),
            ))
            ->with(
                Get::of('bar')->handle(function($state, $message, $continuation, $details) use ($now, $ts) {
                    $this->assertNull($state);
                    $this->assertFalse($details->redelivered());
                    $this->assertSame('text/plain', $message->contentType()->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ));
                    $this->assertSame('gzip', $message->contentEncoding()->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ));

                    $this->assertSame(true, $message->headers()->get('bits')->match(
                        static fn($bits) => $bits->first()->match(
                            static fn($bool) => $bool,
                            static fn() => null,
                        ),
                        static fn() => null,
                    ));
                    $this->assertSame(0.1, $message->headers()->get('decimal')->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ));
                    $this->assertSame('bar', $message->headers()->get('longstr')->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ));
                    $this->assertSame(true, $message->headers()->get('array')->match(
                        static fn($value) => $value->first()->match(
                            static fn($first) => $first->original()->first()->match(
                                static fn($bool) => $bool,
                                static fn() => null,
                            ),
                            static fn() => null,
                        ),
                        static fn() => null,
                    ));
                    $this->assertSame(2, $message->headers()->get('long')->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ));
                    $this->assertSame(4, $message->headers()->get('octet')->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ));
                    $this->assertSame(true, $message->headers()->get('table')->match(
                        static fn($value) => $value->get('inner')->match(
                            static fn($value) => $value->original()->first()->match(
                                static fn($bool) => $bool,
                                static fn() => null,
                            ),
                            static fn() => null,
                        ),
                        static fn() => null,
                    ));
                    $this->assertSame(
                        (int) ($ts->milliseconds() / 1000), //timestamp expressed in seconds and not milliseconds
                        $message->headers()->get('timestamp')->match(
                            static fn($value) => (int) ($value->milliseconds() / 1000),
                            static fn() => null,
                        ),
                    );
                    $this->assertSame(6, $message->headers()->get('ulong')->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ));
                    $this->assertSame(7, $message->headers()->get('ulonglong')->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ));
                    $this->assertSame(8, $message->headers()->get('uoctet')->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ));
                    $this->assertSame(9, $message->headers()->get('ushort')->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ));
                    $this->assertNull($message->headers()->get('void')->match(
                        static fn($value) => $value,
                        static fn() => false,
                    ));

                    $this->assertSame(2, $message->deliveryMode()->match(
                        static fn($value) => $value->toInt(),
                        static fn() => null,
                    ));
                    $this->assertSame(5, $message->priority()->match(
                        static fn($value) => $value->toInt(),
                        static fn() => null,
                    ));
                    $this->assertSame('correlation', $message->correlationId()->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ));
                    $this->assertSame('reply', $message->replyTo()->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ));
                    $this->assertSame(10000, $message->expiration()->match(
                        static fn($value) => $value->milliseconds(),
                        static fn() => null,
                    ));
                    $this->assertSame('id', $message->id()->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ));
                    $this->assertSame(
                        $now->format(new TimestampFormat),
                        $message->timestamp()->match(
                            static fn($value) => $value->format(new TimestampFormat),
                            static fn() => null,
                        ),
                    );
                    $this->assertSame('type', $message->type()->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ));
                    $this->assertSame('guest', $message->userId()->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ));
                    $this->assertSame('webcrawler', $message->appId()->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ));

                    return $continuation->ack(true);
                }),
            )
            ->with(Get::of('bar'))
            ->with(Unbind::of('foo', 'bar'))
            ->with(DeleteQueue::of('bar'))
            ->with(DeleteExchange::of('foo'))
            ->run(null)
            ->match(
                static fn($state) => $state,
                static fn($error) => $error,
            );

        $this->assertTrue($result);
    }
}
