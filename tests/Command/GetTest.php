<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command\Get,
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

class GetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Get(
                $this->createMock(Client::class),
                new Consumers(Map::of('string', 'callable')),
            ),
        );
    }

    public function testDefinition()
    {
        $expected = <<<USAGE
innmind:amqp:get queue

Will process a single message from the given queue
USAGE;

        $this->assertSame(
            $expected,
            (new Get(
                $this->createMock(Client::class),
                new Consumers(Map::of('string', 'callable')),
            ))->toString(),
        );
    }

    public function testInvokation()
    {
        $command = new Get(
            $client = $this->createMock(Client::class),
            new Consumers(
                Map::of('string', 'callable')
                    ('foo', $expected = function(){})
            )
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
            ->method('get')
            ->with($this->callback(function($get): bool {
                return $get->queue() === 'foo';
            }))
            ->willReturn($get = $this->createMock(Basic\Get::class));
        $get
            ->expects($this->once())
            ->method('__invoke')
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
            new Arguments(Map::of('string', 'string')('queue', 'foo')),
            new Options
        ));
    }

    public function testCloseEvenOnException()
    {
        $command = new Get(
            $client = $this->createMock(Client::class),
            new Consumers(
                Map::of('string', 'callable')
                    ('foo', function(){})
            )
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
            ->method('get')
            ->with($this->callback(function($get): bool {
                return $get->queue() === 'foo';
            }))
            ->willReturn($get = $this->createMock(Basic\Get::class));
        $get
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new \Exception));

        $this->expectException(\Exception::class);

        $command(
            $this->createMock(Environment::class),
            new Arguments(Map::of('string', 'string')('queue', 'foo')),
            new Options
        );
    }
}
