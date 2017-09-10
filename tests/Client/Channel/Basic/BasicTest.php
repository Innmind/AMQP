<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Basic;

use Innmind\AMQP\{
    Client\Channel\Basic\Basic,
    Client\Channel\Basic as BasicInterface,
    Transport\Connection,
    Transport\Frame\Channel,
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
    Exception\Reject,
    Exception\Requeue,
    Exception\Cancel
};
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    TimeContinuum\Earth
};
use Innmind\Url\Url;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

/**
 * Qos and Recover are not tested as RabbitMQ (used for the tests) doesn't
 * implement them
 */
class BasicTest extends TestCase
{
    private $basic;
    private $connection;

    public function setUp()
    {
        $this->basic = new Basic(
            $this->connection = new Connection(
                Transport::tcp(),
                Url::fromString('//guest:guest@localhost:5672/'),
                new Protocol(new ValueTranslator),
                new ElapsedPeriod(1000),
                new Earth
            ),
            new Channel(1)
        );
        $this->connection
            ->send(
                $this->connection->protocol()->channel()->open(new Channel(1))
            )
            ->wait('channel.open-ok');
    }

    public function tearDown()
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
            (new Publish(new Generic(new Str('foobar'))))->to('amq.direct')
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
                (new Publish(new Generic(new Str('foobar'.$i))))->to('amq.direct')
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
                    $this->assertSame('foobar'.$calls, (string) $message->body());
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
                (new Publish(new Generic(new Str('foobar'.$i))))->to('amq.direct')
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
                (new Publish(new Generic(new Str('foobar'.$i))))->to('amq.direct')
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
                (new Publish(new Generic(new Str('foobar'.$i))))->to('amq.direct')
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
                    $this->assertSame('foobar'.$calls, (string) $message->body());
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
                (new Publish(new Generic(new Str('foobar'.$i))))->to('amq.direct')
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
                    $this->assertSame('foobar'.$filtered, (string) $message->body());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    ++$filtered;

                    return ((int) (string) $message->body()->substring(-1) % 2) === 0;
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
                        (string) $message->body()
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
                (new Publish(new Generic(new Str('foobar'.$i))))->to('amq.direct')
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
                        (string) $message->body()
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
                (new Publish(new Generic(new Str('foobar'.$i))))->to('amq.direct')
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
                        (string) $message->body()
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
                $publish = (new Publish(new Generic(new Str('foobar'))))->to('amq.direct')
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
                    $this->assertSame('foobar', (string) $message->body());
                    $this->assertFalse($redelivered);
                    $this->assertSame('amq.direct', $exchange);
                    $this->assertSame('', $routingKey);
                    $this->assertSame(1, $messageCount); //1 left in the queue
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
                $publish = (new Publish(new Generic(new Str('foobar'))))->to('amq.direct')
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
                    $this->assertSame('foobar', (string) $message->body());
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
            (new Publish(new Generic(new Str('foobar'))))->to('amq.direct')
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
            (new Publish(new Generic(new Str('foobar'))))->to('amq.direct')
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
            (new Publish(new Generic(new Str('foobar'))))->to('amq.direct')
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
            $this->assertSame('foobar', (string) $message->body());
        });
        $this->assertTrue($called);
    }

    public function testPublish()
    {
        $this->assertSame(
            $this->basic,
            $this->basic->publish(
                new Publish(new Generic(new Str('foobar')))
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
            (new Publish(new Generic(new Str('foobar'))))->to('amq.direct')
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
            (new Publish(new Generic(new Str('foobar'))))->to('amq.direct')
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
}
