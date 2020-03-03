<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Basic;

use Innmind\AMQP\{
    Client\Channel\Basic\Basic,
    Client\Channel\Basic as BasicInterface,
    Transport\Connection\Connection,
    Transport\Frame\Channel,
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
    Transport\Protocol\v091\Protocol,
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
use Innmind\OperatingSystem\Remote;
use Innmind\Server\Control\Server;
use Innmind\Math\Algebra\Integer;
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
                new Remote\Generic($this->createMock(Server::class))
            ),
            new Channel(1)
        );
        $this->connection
            ->send(
                $this->connection->protocol()->channel()->open(new Channel(1))
            )
            ->wait('channel.open-ok');
    }

    public function tearDown(): void
    {
        $this->connection
            ->send(
                $this->connection->protocol()->channel()->close(
                    new Channel(1),
                    new Close
                )
            )
            ->wait('channel.close-ok');
        $this->connection->close();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(BasicInterface::class, $this->basic);
    }

    public function testAck()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_ack')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_ack')
            ))
            ->wait('queue.bind-ok');
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct')
        );
        $frame = $this
            ->connection
            ->send($this->connection->protocol()->basic()->get(
                new Channel(1),
                new Get('test_ack')
            ))
            ->wait('basic.get-ok');
        $deliveryTag = $frame
            ->values()
            ->first()
            ->original()
            ->value();
        $this->connection->wait(); //header
        $this->connection->wait(); //body

        $this->assertSame(
            $this->basic,
            $this->basic->ack(new Ack($deliveryTag))
        );
    }

    public function testCancel()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_cancel')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->basic()->consume(
                new Channel(1),
                (new Consume('test_cancel'))->withConsumerTag('test_cancel_tag')
            ))
            ->wait('basic.consume-ok');

        $this->assertSame(
            $this->basic,
            $this->basic->cancel(
                new CancelCommand('test_cancel_tag')
            )
        );
    }

    public function testCancelWithoutWaiting()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_cancel')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->basic()->consume(
                new Channel(1),
                (new Consume('test_cancel'))->withConsumerTag('test_cancel_tag')
            ))
            ->wait('basic.consume-ok');

        $this->assertSame(
            $this->basic,
            $this->basic->cancel(
                (new CancelCommand('test_cancel_tag'))->dontWait()
            )
        );
    }

    public function testConsume()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_consume')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_consume')
            ))
            ->wait('queue.bind-ok');

        foreach (range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct')
            );
        }

        $calls = 0;

        $this->assertNull(
            $this
                ->basic
                ->consume(
                    new Consume('test_consume')
                )
                ->foreach(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey
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
                })
        );
        $this->assertSame(2, $calls);
        $called = false;
        $this->basic->get(new Get('test_consume'))(function(
            $message,
            $redelivered,
            $exchange,
            $routingKey,
            $messageCount
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
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_consume')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_consume')
            ))
            ->wait('queue.bind-ok');

        foreach (range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct')
            );
        }


        $consumer = $this->basic->consume(new Consume('test_consume'));
        $this->assertNull(
            $consumer
                ->take(4)
                ->foreach(function(): void {
                    //pass
                })
        );
        $called = false;
        $consumer->foreach(function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testCancelConsumerOnError()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_consume')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_consume')
            ))
            ->wait('queue.bind-ok');

        foreach (range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct')
            );
        }


        $consumer = $this->basic->consume(new Consume('test_consume'));

        try {
            $consumer->foreach(function(): void {
                throw new \Exception('error');
            });
        } catch (\Exception $e) {
            $this->assertSame('error', $e->getMessage());
        }

        $called = false;
        $consumer->foreach(function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testConsumeSpecifiedAmount()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_consume')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_consume')
            ))
            ->wait('queue.bind-ok');

        foreach (range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct')
            );
        }

        $calls = 0;

        $this->assertNull(
            $this
                ->basic
                ->consume(
                    new Consume('test_consume')
                )
                ->take(2)
                ->foreach(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey
                ) use (
                    &$calls
                ): void {
                    $this->assertSame('foobar'.$calls, $message->body()->toString());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    ++$calls;
                })
        );
        $this->assertSame(2, $calls);
        $called = false;
        $this->basic->get(new Get('test_consume'))(function(
            $message,
            $redelivered,
            $exchange,
            $routingKey,
            $messageCount
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
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_consume')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_consume')
            ))
            ->wait('queue.bind-ok');

        foreach (range(0, 5) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct')
            );
        }

        $calls = 0;
        $filtered = 0;

        $this->assertNull(
            $this
                ->basic
                ->consume(
                    new Consume('test_consume')
                )
                ->take(2)
                ->filter(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey
                ) use (
                    &$filtered
                ): bool {
                    $this->assertSame('foobar'.$filtered, $message->body()->toString());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    ++$filtered;

                    return ((int) $message->body()->substring(-1)->toString() % 2) === 0;
                })
                ->foreach(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey
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
                })
        );
        $this->assertSame(2, $calls);
        $this->assertSame(3, $filtered);

        $called = false;
        $this->basic->get(new Get('test_consume'))(function(
            $message,
            $redelivered,
            $exchange,
            $routingKey,
            $messageCount
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
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_consume')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_consume')
            ))
            ->wait('queue.bind-ok');

        foreach (range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct')
            );
        }

        $calls = 0;

        $this->assertNull(
            $this
                ->basic
                ->consume(
                    new Consume('test_consume')
                )
                ->take(4) //otherwise it will hang forever
                ->foreach(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey
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
                })
        );
        $this->assertSame(4, $calls);

        $called = false;
        $this->basic->get(new Get('test_consume'))(function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testRequeueInConsume()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_consume')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_consume')
            ))
            ->wait('queue.bind-ok');

        foreach (range(0, 3) as $i) {
            $this->basic->publish(
                (new Publish(new Generic(Str::of('foobar'.$i))))->to('amq.direct')
            );
        }

        $calls = 0;

        $this->assertNull(
            $this
                ->basic
                ->consume(
                    new Consume('test_consume')
                )
                ->take(4) //otherwise it will process all messages ()
                ->foreach(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey
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
                })
        );
        $this->assertSame(4, $calls);

        $called = false;
        $this->basic->get(new Get('test_consume'))(function(
            $message,
            $redelivered,
            $exchange,
            $routingKey,
            $messageCount
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
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_get')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_get')
            ))
            ->wait('queue.bind-ok');
        $this
            ->basic
            ->publish(
                $publish = (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct')
            )
            ->publish($publish);
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get')
                )(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey,
                    int $messageCount
                ) use (
                    &$called
                ): void {
                    $called = true;
                    $this->assertSame('foobar', $message->body()->toString());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    $this->assertSame(1, $messageCount); //1 left in the queue
                })
        );
        $this->assertTrue($called);
    }

    public function testGetMessageWithAllProperties()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_get')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_get')
            ))
            ->wait('queue.bind-ok');
        $message = (new Generic(Str::of('foobar')))
            ->withContentType(new ContentType('text', 'plain'))
            ->withContentEncoding(new ContentEncoding('gzip'))
            ->withHeaders(
                Map::of('string', 'mixed')
                    ->put('bits', new Bits(true))
                    ->put('decimal', new Decimal(new Integer(1), new Integer(1)))
                    ->put('longstr', new LongString(Str::of('bar')))
                    ->put('array', new Sequence(new Bits(true)))
                    ->put('long', new SignedLongInteger(new Integer(2)))
                    ->put('octet', new SignedOctet(new Integer(4)))
                    ->put('table', new Table(Map::of('string', Value::class)(
                        'inner',
                        new Bits(true)
                    )))
                    ->put('timestamp', new Timestamp($ts = new Now))
                    ->put('ulong', new UnsignedLongInteger(new Integer(6)))
                    ->put('ulonglong', new UnsignedLongLongInteger(new Integer(7)))
                    ->put('uoctet', new UnsignedOctet(new Integer(8)))
                    ->put('ushort', new UnsignedShortInteger(new Integer(9)))
                    ->put('void', new VoidValue)
            )
            ->withDeliveryMode(DeliveryMode::persistent())
            ->withPriority(new Priority(5))
            ->withCorrelationId(new CorrelationId('correlation'))
            ->withReplyTo(new ReplyTo('reply'))
            ->withExpiration(new ElapsedPeriod(10000))
            ->withId(new Id('id'))
            ->withTimestamp($now = new Now)
            ->withType(new Type('type'))
            ->withUserId(new UserId('guest'))
            ->withAppId(new AppId('webcrawler'));

        $this->assertSame(
            $this->basic,
            $this->basic->publish((new Publish($message))->to('amq.direct'))
        );
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get')
                )(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey,
                    int $messageCount
                ) use (
                    &$called,
                    $now,
                    $ts
                ): void {
                    $called = true;
                    $this->assertSame('text/plain', (string) $message->contentType());
                    $this->assertSame('gzip', (string) $message->contentEncoding());

                    $this->assertSame(true, $message->headers()->get('bits')->first());
                    $this->assertSame(0.1, $message->headers()->get('decimal')->value());
                    $this->assertSame('bar', $message->headers()->get('longstr')->toString());
                    $this->assertSame(true, $message->headers()->get('array')->first()->original()->first());
                    $this->assertSame(2, $message->headers()->get('long')->value());
                    $this->assertSame(4, $message->headers()->get('octet')->value());
                    $this->assertSame(true, $message->headers()->get('table')->get('inner')->original()->first());
                    $this->assertSame(
                        (int) ($ts->milliseconds() / 1000), //timestamp expressed in seconds and not milliseconds
                        (int) ($message->headers()->get('timestamp')->milliseconds() / 1000)
                    );
                    $this->assertSame(6, $message->headers()->get('ulong')->value());
                    $this->assertSame(7, $message->headers()->get('ulonglong')->value());
                    $this->assertSame(8, $message->headers()->get('uoctet')->value());
                    $this->assertSame(9, $message->headers()->get('ushort')->value());
                    $this->assertNull($message->headers()->get('void'));

                    $this->assertSame(2, $message->deliveryMode()->toInt());
                    $this->assertSame(5, $message->priority()->toInt());
                    $this->assertSame('correlation', (string) $message->correlationId());
                    $this->assertSame('reply', (string) $message->replyTo());
                    $this->assertSame(10000, $message->expiration()->milliseconds());
                    $this->assertSame('id', (string) $message->id());
                    $this->assertSame(
                        $now->format(new TimestampFormat),
                        $message->timestamp()->format(new TimestampFormat)
                    );
                    $this->assertSame('type', (string) $message->type());
                    $this->assertSame('guest', (string) $message->userId());
                    $this->assertSame('webcrawler', (string) $message->appId());
                    $this->assertSame('foobar', $message->body()->toString());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    $this->assertSame(0, $messageCount); //1 left in the queue
                })
        );
        $this->assertTrue($called);
    }

    public function testReuseGet()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_get')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_get')
            ))
            ->wait('queue.bind-ok');
        $this
            ->basic
            ->publish(
                $publish = (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct')
            )
            ->publish($publish);
        $called = false;

        $get = $this->basic->get(new Get('test_get'));
        $this->assertNull(
            $get(function(
                    Message $message,
                    bool $redelivered,
                    string $exchange,
                    string $routingKey,
                    int $messageCount
                ) use (
                    &$called
                ): void {
                    $called = true;
                    $this->assertSame('foobar', $message->body()->toString());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    $this->assertSame(1, $messageCount); //1 left in the queue
                })
        );
        $this->assertTrue($called);
        $called = false;
        $this->assertNull($get(function() use (&$called) {
            $called = true;
        }));
        $this->assertFalse($called);
    }

    public function testGetEmpty()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_get')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_get')
            ))
            ->wait('queue.bind-ok');
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get')
                )(function() use (&$called): void {
                    $called = true;
                })
        );
        $this->assertFalse($called);
    }

    public function testRejectGet()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_get')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_get')
            ))
            ->wait('queue.bind-ok');
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct')
        );
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get')
                )(function() use (&$called): void {
                    $called = true;
                    throw new Reject;
                })
        );
        $this->assertTrue($called);
        $called = false;
        $this->basic->get(new Get('test_get'))(function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testRejectGetOnError()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_get')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_get')
            ))
            ->wait('queue.bind-ok');
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct')
        );
        $called = false;

        try {
            $this
                ->basic
                ->get(
                    new Get('test_get')
                )(function() use (&$called): void {
                    $called = true;
                    throw new \Exception('error');
                });
        } catch (\Exception $e) {
            $this->assertSame('error', $e->getMessage());
        }

        $this->assertTrue($called);
        $called = false;
        $this->basic->get(new Get('test_get'))(function() use (&$called): void {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testRequeueGet()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_get')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_get')
            ))
            ->wait('queue.bind-ok');
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct')
        );
        $called = false;

        $this->assertNull(
            $this
                ->basic
                ->get(
                    new Get('test_get')
                )(function() use (&$called): void {
                    $called = true;
                    throw new Requeue;
                })
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
        $this->assertSame(
            $this->basic,
            $this->basic->publish(
                new Publish(new Generic(Str::of('foobar')))
            )
        );
    }

    public function testReject()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_reject')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_reject')
            ))
            ->wait('queue.bind-ok');
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct')
        );
        $frame = $this
            ->connection
            ->send($this->connection->protocol()->basic()->get(
                new Channel(1),
                new Get('test_reject')
            ))
            ->wait('basic.get-ok');
        $deliveryTag = $frame
            ->values()
            ->first()
            ->original()
            ->value();
        $this->connection->wait(); //header
        $this->connection->wait(); //body

        $this->assertSame(
            $this->basic,
            $this->basic->reject(new RejectCommand($deliveryTag))
        );
    }

    public function testRequeue()
    {
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->declare(
                new Channel(1),
                Declaration::temporary()
                    ->exclusive()
                    ->withName('test_requeue')
            ))
            ->wait('queue.declare-ok');
        $this
            ->connection
            ->send($this->connection->protocol()->queue()->bind(
                new Channel(1),
                new Binding('amq.direct', 'test_requeue')
            ))
            ->wait('queue.bind-ok');
        $this->basic->publish(
            (new Publish(new Generic(Str::of('foobar'))))->to('amq.direct')
        );
        $frame = $this
            ->connection
            ->send($this->connection->protocol()->basic()->get(
                new Channel(1),
                new Get('test_requeue')
            ))
            ->wait('basic.get-ok');
        $deliveryTag = $frame
            ->values()
            ->first()
            ->original()
            ->value();
        $this->connection->wait(); //header
        $this->connection->wait(); //body

        $this->assertSame(
            $this->basic,
            $this->basic->reject(RejectCommand::requeue($deliveryTag))
        );
    }

    public function testQos()
    {
        //only prefectch count can be tested as prefetch size is not implemented
        //by rabbitmq
        $this->assertSame(
            $this->basic,
            $this->basic->qos(new Qos(0, 50))
        );
        $this->assertSame(
            $this->basic,
            $this->basic->qos(Qos::global(0, 50))
        );
    }
}
