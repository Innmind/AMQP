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
};
use Innmind\Stream\Writable;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class PurgeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Purge($this->createMock(Client::class))
        );
    }

    public function testDefinition()
    {
        $expected = <<<USAGE
innmind:amqp:purge queue

Will delete all messages for the given queue
USAGE;

        $this->assertSame($expected, (new Purge($this->createMock(Client::class)))->toString());
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
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->never())
            ->method('exit');

        $this->assertNull($purge(
            $env,
            new Arguments(Map::of('string', 'string')('queue', 'foo')),
            new Options
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
            $this->createMock(Environment::class),
            new Arguments(Map::of('string', 'string')('queue', 'foo')),
            new Options
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
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(1);
        $env
            ->expects($this->once())
            ->method('error')
            ->willReturn($error = $this->createMock(Writable::class));
        $error
            ->expects($this->once())
            ->method('write')
            ->with($this->callback(static function($str): bool {
                return $str->toString() === 'Purging "foo" failed';
            }));

        $this->assertNull($purge(
            $env,
            new Arguments(Map::of('string', 'string')('queue', 'foo')),
            new Options
        ));
    }
}
