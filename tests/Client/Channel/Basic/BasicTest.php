<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Basic;

use Innmind\AMQP\{
    Client\Channel\Basic\Basic,
    Client\Channel\Basic as BasicInterface,
    Transport\Connection\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\Decimal,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\Sequence,
    Transport\Frame\Value\SignedLongInteger,
    Transport\Frame\Value\SignedOctet,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\Timestamp,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\VoidValue,
    Transport\Protocol,
    Transport\Protocol\ArgumentTranslator\ValueTranslator,
    Model\Channel\Close,
    Model\Queue\Declaration,
    Model\Queue\Binding,
    Model\Basic\Publish,
    Model\Basic\Qos,
    Model\Basic\Get,
    Model\Basic\Ack,
    Model\Basic\Consume,
    Model\Basic\Cancel as CancelCommand,
    Model\Basic\Reject as RejectCommand,
    Model\Basic\Recover,
    Model\Basic\Message,
    Model\Basic\Message\Generic,
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
    TimeContinuum\Format\Timestamp as TimestampFormat,
    Exception\Reject,
    Exception\Requeue,
    Exception\Cancel,
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\Earth\{
    ElapsedPeriod,
    Clock,
    PointInTime\Now,
};
use Innmind\Url\Url;
use Innmind\OperatingSystem\{
    Remote,
    Sockets,
};
use Innmind\Server\Control\Server;
use Innmind\Immutable\{
    Str,
    Map,
};
use PHPUnit\Framework\TestCase;

/**
 * Recover are not tested as RabbitMQ (used for the tests) doesn't
 * implement them
 */
class BasicTest extends TestCase
{
    private $basic;
    private $connection;

    public function setUp(): void
    {
        $this->basic = new Basic(
            $this->connection = new Connection(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5672/'),
                new Protocol(new ValueTranslator),
                new ElapsedPeriod(1000),
                new Clock,
                Remote\Generic::of($this->createMock(Server::class), new Clock),
                Sockets\Unix::of(),
            ),
            new Channel(1),
        );
        $this->connection->send(
            $this->connection->protocol()->channel()->open(new Channel(1)),
        );
        $this->connection->wait(Method::channelOpenOk);
    }

    public function tearDown(): void
    {
        $this->connection->send(
            $this->connection->protocol()->channel()->close(
                new Channel(1),
                new Close,
            ),
        );
        $this->connection->wait(Method::channelCloseOk);
        $this->connection->close();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(BasicInterface::class, $this->basic);
    }

    public function testAck()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_ack'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_ack'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct'),
        );
        $this->connection->send($this->connection->protocol()->basic()->get(
            new Channel(1),
            new Get('test_ack'),
        ));
        $frame = $this->connection->wait(Method::basicGetOk);
        $deliveryTag = $frame
            ->values()
            ->first()
            ->match(
                static fn($value) => $value,
                static fn() => null,
            )
            ->original();
        $this->connection->wait(); //header
        $this->connection->wait(); //body

        $this->assertNull(
            $this->basic->ack(new Ack($deliveryTag)),
        );
    }

    public function testCancel()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_cancel'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->basic()->consume(
            new Channel(1),
            (new Consume('test_cancel'))->withConsumerTag('test_cancel_tag'),
        ));
        $this->connection->wait(Method::basicConsumeOk);

        $this->assertNull(
            $this->basic->cancel(
                new CancelCommand('test_cancel_tag'),
            ),
        );
    }

    public function testCancelWithoutWaiting()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_cancel'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->basic()->consume(
            new Channel(1),
            (new Consume('test_cancel'))->withConsumerTag('test_cancel_tag'),
        ));
        $this->connection->wait(Method::basicConsumeOk);

        $this->assertNull(
            $this->basic->cancel(
                (new CancelCommand('test_cancel_tag'))->dontWait(),
            ),
        );
    }

    public function testConsume()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_consume'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_consume'),
        ));
        $this->connection->wait(Method::queueBindOk);

        foreach (\range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct'),
            );
        }

        $calls = 0;

        $this->assertNull(
            $this
                ->basic
                ->consume(
                    new Consume('test_consume'),
                )
                ->foreach(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey,
                ) use (
                    &$calls
                ): void {
                    $this->assertSame('foobar'.$calls, $message->body()->toString());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    ++$calls;

                    if ($calls === 2) {
                        throw new Cancel;
                    }
                }),
        );
        $this->assertSame(2, $calls);
        $called = false;
        $this->basic->get(new Get('test_consume'))(function(
            $message,
            $redelivered,
            $exchange,
            $routingKey,
            $messageCount,
        ) use (
            &$called
        ): void {
            $called = true;
            $this->assertSame(1, $messageCount); //assert that we indeed consumed only 2 messages earlier
        });
        $this->assertTrue($called);
    }

    public function testReuseConsumer()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_consume'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_consume'),
        ));
        $this->connection->wait(Method::queueBindOk);

        foreach (\range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct'),
            );
        }

        $consumer = $this->basic->consume(new Consume('test_consume'));
        $consumer->take(4);
        $this->assertNull(
            $consumer->foreach(static function(): void {
                //pass
            }),
        );
        $called = false;
        $consumer->foreach(static function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testCancelConsumerOnError()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_consume'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_consume'),
        ));
        $this->connection->wait(Method::queueBindOk);

        foreach (\range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct'),
            );
        }

        $consumer = $this->basic->consume(new Consume('test_consume'));

        try {
            $consumer->foreach(static function(): void {
                throw new \Exception('error');
            });
        } catch (\Exception $e) {
            $this->assertSame('error', $e->getMessage());
        }

        $called = false;
        $consumer->foreach(static function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testConsumeSpecifiedAmount()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_consume'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_consume'),
        ));
        $this->connection->wait(Method::queueBindOk);

        foreach (\range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct'),
            );
        }

        $calls = 0;

        $consumer = $this
            ->basic
            ->consume(
                new Consume('test_consume'),
            );
        $consumer->take(2);
        $this->assertNull(
            $consumer->foreach(function(
                Message $message,
                bool $redelivered,
                string $exchange,
                string $routingKey,
            ) use (
                &$calls
            ): void {
                $this->assertSame('foobar'.$calls, $message->body()->toString());
                $this->assertFalse($redelivered);
                $this->assertSame('amq.direct', $exchange);
                $this->assertSame('', $routingKey);
                ++$calls;
            }),
        );
        $this->assertSame(2, $calls);
        $called = false;
        $this->basic->get(new Get('test_consume'))(function(
            $message,
            $redelivered,
            $exchange,
            $routingKey,
            $messageCount,
        ) use (
            &$called
        ): void {
            $called = true;
            $this->assertSame(1, $messageCount); //assert that we indeed consumed only 2 messages earlier
        });
        $this->assertTrue($called);
    }

    public function testConsumeWithFilter()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_consume'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_consume'),
        ));
        $this->connection->wait(Method::queueBindOk);

        foreach (\range(0, 5) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct'),
            );
        }

        $calls = 0;
        $filtered = 0;

        $consumer = $this
            ->basic
            ->consume(
                new Consume('test_consume'),
            );
        $consumer->take(2);
        $consumer->filter(function(
            Message $message,
            bool $redelivered,
            string $exchange,
            string $routingKey,
        ) use (
            &$filtered
        ): bool {
            $this->assertSame('foobar'.$filtered, $message->body()->toString());
            $this->assertFalse($redelivered);
            $this->assertSame('amq.direct', $exchange);
            $this->assertSame('', $routingKey);
            ++$filtered;

            return ((int) $message->body()->substring(-1)->toString() % 2) === 0;
        });
        $this->assertNull(
            $consumer->foreach(function(
                Message $message,
                bool $redelivered,
                string $exchange,
                string $routingKey,
            ) use (
                &$calls,
                &$filtered
            ): void {
                $this->assertSame(
                    'foobar'.($calls * 2),
                    $message->body()->toString(),
                );
                $this->assertFalse($redelivered);
                $this->assertSame('amq.direct', $exchange);
                $this->assertSame('', $routingKey);
                ++$calls;
            }),
        );
        $this->assertSame(2, $calls);
        $this->assertSame(3, $filtered);

        $called = false;
        $this->basic->get(new Get('test_consume'))(function(
            $message,
            $redelivered,
            $exchange,
            $routingKey,
            $messageCount,
        ) use (
            &$called
        ): void {
            $called = true;
            $this->assertTrue($redelivered);
            $this->assertSame(3, $messageCount); //assert that we indeed consumed only 2 messages earlier
        });
        $this->assertTrue($called);
    }

    public function testRejectInConsume()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_consume'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_consume'),
        ));
        $this->connection->wait(Method::queueBindOk);

        foreach (\range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct'),
            );
        }

        $calls = 0;

        $consumer = $this
            ->basic
            ->consume(
                new Consume('test_consume'),
            );
        $consumer->take(4); //otherwise it will hang forever
        $this->assertNull(
            $consumer->foreach(function(
                Message $message,
                bool $redelivered,
                string $exchange,
                string $routingKey,
            ) use (
                &$calls
            ): void {
                $this->assertSame(
                    'foobar'.$calls,
                    $message->body()->toString(),
                );
                $this->assertFalse($redelivered);
                $this->assertSame('amq.direct', $exchange);
                $this->assertSame('', $routingKey);
                ++$calls;

                if ($calls >= 1) {
                    throw new Reject;
                }
            }),
        );
        $this->assertSame(4, $calls);

        $called = false;
        $this->basic->get(new Get('test_consume'))(static function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testRequeueInConsume()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_consume'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_consume'),
        ));
        $this->connection->wait(Method::queueBindOk);

        foreach (\range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct'),
            );
        }

        $calls = 0;

        $consumer = $this
            ->basic
            ->consume(
                new Consume('test_consume'),
            );
        $consumer->take(4); //otherwise it will process all messages ()
        $this->assertNull(
            $consumer->foreach(function(
                Message $message,
                bool $redelivered,
                string $exchange,
                string $routingKey,
            ) use (
                &$calls
            ): void {
                $this->assertSame(
                    'foobar'.$calls,
                    $message->body()->toString(),
                );
                $this->assertFalse($redelivered);
                $this->assertSame('amq.direct', $exchange);
                $this->assertSame('', $routingKey);
                ++$calls;

                if ($calls > 1) {
                    throw new Requeue;
                }
            }),
        );
        $this->assertSame(4, $calls);

        $called = false;
        $this->basic->get(new Get('test_consume'))(function(
            $message,
            $redelivered,
            $exchange,
            $routingKey,
            $messageCount,
        ) use (
            &$called
        ): void {
            $called = true;
            $this->assertTrue($redelivered);
            $this->assertSame(2, $messageCount);
        });
        $this->assertTrue($called);
    }

    public function testGet()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_get'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_get'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $this->basic->publish(
            $publish = (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct'),
        );
        $this->basic->publish($publish);
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get'),
                )(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey,
                    int $messageCount,
                ) use (
                    &$called
                ): void {
                    $called = true;
                    $this->assertSame('foobar', $message->body()->toString());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    $this->assertSame(1, $messageCount); //1 left in the queue
                }),
        );
        $this->assertTrue($called);
    }

    public function testGetMessageWithAllProperties()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_get'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_get'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $message = (new Generic(Str::of('foobar')))
            ->withContentType(new ContentType('text', 'plain'))
            ->withContentEncoding(new ContentEncoding('gzip'))
            ->withHeaders(
                Map::of(
                    ['bits', Bits::of(true)],
                    ['decimal', Decimal::of(1, 1)],
                    ['longstr', LongString::literal('bar')],
                    ['array', Sequence::of(Bits::of(true))],
                    ['long', SignedLongInteger::of(2)],
                    ['octet', SignedOctet::of(4)],
                    ['table', Table::of(Map::of(['inner', Bits::of(true)]))],
                    ['timestamp', Timestamp::of($ts = new Now)],
                    ['ulong', UnsignedLongInteger::of(6)],
                    ['ulonglong', UnsignedLongLongInteger::of(7)],
                    ['uoctet', UnsignedOctet::of(8)],
                    ['ushort', UnsignedShortInteger::of(9)],
                    ['void', new VoidValue],
                ),
            )
            ->withDeliveryMode(DeliveryMode::persistent)
            ->withPriority(Priority::five)
            ->withCorrelationId(new CorrelationId('correlation'))
            ->withReplyTo(new ReplyTo('reply'))
            ->withExpiration(new ElapsedPeriod(10000))
            ->withId(new Id('id'))
            ->withTimestamp($now = new Now)
            ->withType(new Type('type'))
            ->withUserId(new UserId('guest'))
            ->withAppId(new AppId('webcrawler'));

        $this->assertNull(
            $this->basic->publish((new Publish($message))->to('amq.direct')),
        );
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get'),
                )(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey,
                    int $messageCount,
                ) use (
                    &$called,
                    $now,
                    $ts
                ): void {
                    $called = true;
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
                    $this->assertSame('foobar', $message->body()->toString());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    $this->assertSame(0, $messageCount); //1 left in the queue
                }),
        );
        $this->assertTrue($called);
    }

    public function testReuseGet()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_get'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_get'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $this->basic->publish(
            $publish = (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct'),
        );
        $this->basic->publish($publish);
        $called = false;

        $get = $this->basic->get(new Get('test_get'));
        $this->assertNull(
            $get(function(
                Message $message,
                bool $redelivered,
                string $exchange,
                string $routingKey,
                int $messageCount,
            ) use (
                &$called
            ): void {
                $called = true;
                $this->assertSame('foobar', $message->body()->toString());
                $this->assertFalse($redelivered);
                $this->assertSame('amq.direct', $exchange);
                $this->assertSame('', $routingKey);
                $this->assertSame(1, $messageCount); //1 left in the queue
            }),
        );
        $this->assertTrue($called);
        $called = false;
        $this->assertNull($get(static function() use (&$called) {
            $called = true;
        }));
        $this->assertFalse($called);
    }

    public function testGetEmpty()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_get'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_get'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get'),
                )(static function() use (&$called): void {
                    $called = true;
                }),
        );
        $this->assertFalse($called);
    }

    public function testRejectGet()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_get'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_get'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct'),
        );
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get'),
                )(static function() use (&$called): void {
                    $called = true;

                    throw new Reject;
                }),
        );
        $this->assertTrue($called);
        $called = false;
        $this->basic->get(new Get('test_get'))(static function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testRejectGetOnError()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_get'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_get'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct'),
        );
        $called = false;

        try {
            $this
                ->basic
                ->get(
                    new Get('test_get'),
                )(static function() use (&$called): void {
                    $called = true;

                    throw new \Exception('error');
                });
        } catch (\Exception $e) {
            $this->assertSame('error', $e->getMessage());
        }

        $this->assertTrue($called);
        $called = false;
        $this->basic->get(new Get('test_get'))(static function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testRequeueGet()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_get'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_get'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct'),
        );
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get'),
                )(static function() use (&$called): void {
                    $called = true;

                    throw new Requeue;
                }),
        );
        $this->assertTrue($called);
        $called = false;
        $this->basic->get(new Get('test_get'))(function($message) use (&$called): void {
            $called = true;
            $this->assertSame('foobar', $message->body()->toString());
        });
        $this->assertTrue($called);
    }

    public function testPublish()
    {
        $this->assertNull(
            $this->basic->publish(
                new Publish(new Generic(Str::of('foobar'))),
            ),
        );
    }

    public function testReject()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_reject'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_reject'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct'),
        );
        $this->connection->send($this->connection->protocol()->basic()->get(
            new Channel(1),
            new Get('test_reject'),
        ));
        $frame = $this->connection->wait(Method::basicGetOk);
        $deliveryTag = $frame
            ->values()
            ->first()
            ->match(
                static fn($value) => $value,
                static fn() => null,
            )
            ->original();
        $this->connection->wait(); //header
        $this->connection->wait(); //body

        $this->assertNull(
            $this->basic->reject(new RejectCommand($deliveryTag)),
        );
    }

    public function testRequeue()
    {
        $this->connection->send($this->connection->protocol()->queue()->declare(
            new Channel(1),
            Declaration::temporary()
                ->exclusive()
                ->withName('test_requeue'),
        ));
        $this->connection->wait(Method::queueDeclareOk);
        $this->connection->send($this->connection->protocol()->queue()->bind(
            new Channel(1),
            new Binding('amq.direct', 'test_requeue'),
        ));
        $this->connection->wait(Method::queueBindOk);
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct'),
        );
        $this->connection->send($this->connection->protocol()->basic()->get(
            new Channel(1),
            new Get('test_requeue'),
        ));
        $frame = $this->connection->wait(Method::basicGetOk);
        $deliveryTag = $frame
            ->values()
            ->first()
            ->match(
                static fn($value) => $value,
                static fn() => null,
            )
            ->original();
        $this->connection->wait(); //header
        $this->connection->wait(); //body

        $this->assertNull(
            $this->basic->reject(RejectCommand::requeue($deliveryTag)),
        );
    }

    public function testQos()
    {
        //only prefectch count can be tested as prefetch size is not implemented
        //by rabbitmq
        $this->assertNull(
            $this->basic->qos(new Qos(0, 50)),
        );
        $this->assertNull(
            $this->basic->qos(Qos::global(0, 50)),
        );
    }
}
