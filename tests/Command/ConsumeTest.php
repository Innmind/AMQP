<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command\Consume,
    Client,
    Client\Channel,
    Client\Channel\Basic,
    Consumers,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ConsumeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Consume(
                $this->createMock(Client::class),
                new Consumers
            )
        );
    }

    public function testDefinition()
    {
        $expected = <<<USAGE
innmind:amqp:consume queue [number] [prefetch]

Will process messages from the given queue
USAGE;

        $this->assertSame(
            $expected,
            (string) new Consume(
                $this->createMock(Client::class),
                new Consumers
            )
        );
    }

    public function testInvokation()
    {
        $command = new Consume(
            $client = $this->createMock(Client::class),
            new Consumers(
                (new Map('string', 'callable'))
                    ->put('foo', $expected = function(){})
            )
        );
        $client
            ->expects($this->at(0))
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $client
            ->expects($this->at(1))
            ->method('close');
        $channel
            ->expects($this->once())
            ->method('basic')
            ->willReturn($basic = $this->createMock(Basic::class));
        $basic
            ->expects($this->once())
            ->method('consume')
            ->with($this->callback(function($consume): bool {
                return $consume->queue() === 'foo';
            }))
            ->willReturn($consumer = $this->createMock(Basic\Consumer::class));
        $consumer
            ->expects($this->once())
            ->method('foreach')
            ->with($expected);
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->never())
            ->method('exit');
        $env
            ->expects($this->never())
            ->method('output');

        $this->assertNull($command(
            $env,
            new Arguments((new Map('string', 'mixed'))->put('queue', 'foo')),
            new Options
        ));
    }

    public function testCloseEvenOnException()
    {
        $consume = new Consume(
            $client = $this->createMock(Client::class),
            new Consumers(
                (new Map('string', 'callable'))
                    ->put('foo', function(){})
            )
        );
        $client
            ->expects($this->at(0))
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $client
            ->expects($this->at(1))
            ->method('close');
        $channel
            ->expects($this->once())
            ->method('basic')
            ->willReturn($basic = $this->createMock(Basic::class));
        $basic
            ->expects($this->once())
            ->method('consume')
            ->with($this->callback(function($consume): bool {
                return $consume->queue() === 'foo';
            }))
            ->willReturn($consumer = $this->createMock(Basic\Consumer::class));
        $consumer
            ->expects($this->once())
            ->method('foreach')
            ->will($this->throwException(new \Exception));

        $this->expectException(\Exception::class);

        $consume(
            $this->createMock(Environment::class),
            new Arguments((new Map('string', 'mixed'))->put('queue', 'foo')),
            new Options
        );
    }

    public function testConsumeXMessages()
    {
        $command = new Consume(
            $client = $this->createMock(Client::class),
            new Consumers(
                (new Map('string', 'callable'))
                    ->put('foo', $expected = function(){})
            )
        );
        $client
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $channel
            ->expects($this->once())
            ->method('basic')
            ->willReturn($basic = $this->createMock(Basic::class));
        $basic
            ->expects($this->once())
            ->method('qos')
            ->with($this->callback(static function($qos): bool {
                return $qos->prefetchCount() === 42;
            }));
        $basic
            ->expects($this->once())
            ->method('consume')
            ->with($this->callback(function($consume): bool {
                return $consume->queue() === 'foo';
            }))
            ->willReturn($consumer = $this->createMock(Basic\Consumer::class));
        $consumer
            ->expects($this->once())
            ->method('take')
            ->with(42)
            ->will($this->returnSelf());
        $consumer
            ->expects($this->once())
            ->method('foreach')
            ->with($expected);
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->never())
            ->method('exit');
        $env
            ->expects($this->never())
            ->method('output');

        $this->assertNull($command(
            $env,
            new Arguments(
                (new Map('string', 'mixed'))
                    ->put('queue', 'foo')
                    ->put('number', '42')
            ),
            new Options
        ));
    }

    public function testPrefetchMessages()
    {
        $command = new Consume(
            $client = $this->createMock(Client::class),
            new Consumers(
                (new Map('string', 'callable'))
                    ->put('foo', $expected = function(){})
            )
        );
        $client
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $channel
            ->expects($this->once())
            ->method('basic')
            ->willReturn($basic = $this->createMock(Basic::class));
        $basic
            ->expects($this->once())
            ->method('qos')
            ->with($this->callback(static function($qos): bool {
                return $qos->prefetchCount() === 24;
            }));
        $basic
            ->expects($this->once())
            ->method('consume')
            ->with($this->callback(function($consume): bool {
                return $consume->queue() === 'foo';
            }))
            ->willReturn($consumer = $this->createMock(Basic\Consumer::class));
        $consumer
            ->expects($this->once())
            ->method('take')
            ->with(42)
            ->will($this->returnSelf());
        $consumer
            ->expects($this->once())
            ->method('foreach')
            ->with($expected);
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->never())
            ->method('exit');
        $env
            ->expects($this->never())
            ->method('output');

        $this->assertNull($command(
            $env,
            new Arguments(
                (new Map('string', 'mixed'))
                    ->put('queue', 'foo')
                    ->put('number', '42')
                    ->put('prefetch', '24')
            ),
            new Options
        ));
    }
}
