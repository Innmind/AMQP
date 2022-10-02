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
    Console,
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
                new Consumers(Map::of()),
            ),
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
            (new Consume(
                $this->createMock(Client::class),
                new Consumers(Map::of()),
            ))->usage(),
        );
    }

    public function testInvokation()
    {
        $command = new Consume(
            $client = $this->createMock(Client::class),
            new Consumers(
                Map::of(['foo', $expected = static function() {}]),
            ),
        );
        $client
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $client
            ->expects($this->once())
            ->method('close');
        $channel
            ->expects($this->once())
            ->method('basic')
            ->willReturn($basic = $this->createMock(Basic::class));
        $basic
            ->expects($this->once())
            ->method('consume')
            ->with($this->callback(static function($consume): bool {
                return $consume->queue() === 'foo';
            }))
            ->willReturn($consumer = $this->createMock(Basic\Consumer::class));
        $consumer
            ->expects($this->once())
            ->method('foreach')
            ->with($expected);

        $console = $command(
            Console::of(
                Environment\InMemory::of(
                    [],
                    true,
                    ['foo'],
                    [],
                    '/',
                ),
                new Arguments(
                    Map::of(['queue', 'foo']),
                ),
                new Options,
            ),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
        $this->assertSame([], $console->environment()->outputs());
    }

    public function testCloseEvenOnException()
    {
        $consume = new Consume(
            $client = $this->createMock(Client::class),
            new Consumers(
                Map::of(['foo', static function() {}]),
            ),
        );
        $client
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $client
            ->expects($this->once())
            ->method('close');
        $channel
            ->expects($this->once())
            ->method('basic')
            ->willReturn($basic = $this->createMock(Basic::class));
        $basic
            ->expects($this->once())
            ->method('consume')
            ->with($this->callback(static function($consume): bool {
                return $consume->queue() === 'foo';
            }))
            ->willReturn($consumer = $this->createMock(Basic\Consumer::class));
        $consumer
            ->expects($this->once())
            ->method('foreach')
            ->will($this->throwException(new \Exception));

        $this->expectException(\Exception::class);

        $console = $consume(
            Console::of(
                Environment\InMemory::of(
                    [],
                    true,
                    ['foo'],
                    [],
                    '/',
                ),
                new Arguments(
                    Map::of(['queue', 'foo']),
                ),
                new Options,
            ),
        );
    }

    public function testConsumeXMessages()
    {
        $command = new Consume(
            $client = $this->createMock(Client::class),
            new Consumers(
                Map::of(['foo', $expected = static function() {}]),
            ),
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
            ->with($this->callback(static function($consume): bool {
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

        $console = $command(
            Console::of(
                Environment\InMemory::of(
                    [],
                    true,
                    ['foo', '42'],
                    [],
                    '/',
                ),
                new Arguments(
                    Map::of(
                        ['queue', 'foo'],
                        ['number', '42'],
                    ),
                ),
                new Options,
            ),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
        $this->assertSame([], $console->environment()->outputs());
    }

    public function testPrefetchMessages()
    {
        $command = new Consume(
            $client = $this->createMock(Client::class),
            new Consumers(
                Map::of(['foo', $expected = static function() {}]),
            ),
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
            ->with($this->callback(static function($consume): bool {
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

        $console = $command(
            Console::of(
                Environment\InMemory::of(
                    [],
                    true,
                    ['foo', '42', '24'],
                    [],
                    '/',
                ),
                new Arguments(
                    Map::of(
                        ['queue', 'foo'],
                        ['number', '42'],
                        ['prefetch', '24'],
                    ),
                ),
                new Options,
            ),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
        $this->assertSame([], $console->environment()->outputs());
    }
}
