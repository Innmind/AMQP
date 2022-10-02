<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command\Purge,
    Client,
    Client\Channel,
    Client\Channel\Queue,
    Exception\UnexpectedFrame,
    Transport\Frame,
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

class PurgeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Purge($this->createMock(Client::class)),
        );
    }

    public function testDefinition()
    {
        $expected = <<<USAGE
innmind:amqp:purge queue

Will delete all messages for the given queue
USAGE;

        $this->assertSame($expected, (new Purge($this->createMock(Client::class)))->usage());
    }

    public function testInvokation()
    {
        $purge = new Purge($client = $this->createMock(Client::class));
        $client
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $client
            ->expects($this->once())
            ->method('close');
        $channel
            ->expects($this->once())
            ->method('queue')
            ->willReturn($queue = $this->createMock(Queue::class));
        $queue
            ->expects($this->once())
            ->method('purge')
            ->with($this->callback(static function($purge): bool {
                return $purge->name() === 'foo';
            }));

        $console = $purge(
            Console::of(
                Environment\InMemory::of(
                    [],
                    true,
                    ['foo'],
                    [],
                    '/',
                ),
                new Arguments(Map::of(['queue', 'foo'])),
                new Options,
            ),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
    }

    public function testCloseEvenOnException()
    {
        $purge = new Purge($client = $this->createMock(Client::class));
        $client
            ->expects($this->once())
            ->method('channel')
            ->will($this->throwException(new \Exception));
        $client
            ->expects($this->once())
            ->method('close');

        $this->expectException(\Exception::class);

        $purge(
            Console::of(
                Environment\InMemory::of(
                    [],
                    true,
                    ['foo'],
                    [],
                    '/',
                ),
                new Arguments(Map::of(['queue', 'foo'])),
                new Options,
            ),
        );
    }

    public function testFailsWhenUnexpectedFrame()
    {
        $purge = new Purge($client = $this->createMock(Client::class));
        $client
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $channel
            ->expects($this->once())
            ->method('queue')
            ->willReturn($queue = $this->createMock(Queue::class));
        $queue
            ->expects($this->once())
            ->method('purge')
            ->with($this->callback(static function($purge): bool {
                return $purge->name() === 'foo';
            }))
            ->will($this->throwException(new UnexpectedFrame(Frame::heartbeat())));

        $console = $purge(
            Console::of(
                Environment\InMemory::of(
                    [],
                    true,
                    ['foo'],
                    [],
                    '/',
                ),
                new Arguments(Map::of(['queue', 'foo'])),
                new Options,
            ),
        );
        $this->assertSame(1, $console->environment()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
        $this->assertSame(['Purging "foo" failed'], $console->environment()->errors());
    }
}
