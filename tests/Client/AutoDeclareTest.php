<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client\AutoDeclare,
    Client\Channel,
    Client,
    Model\Exchange\Declaration as Exchange,
    Model\Exchange\Type,
    Model\Queue\Declaration as Queue,
    Model\Queue\Binding,
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class AutoDeclareTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Client::class,
            new AutoDeclare($this->createMock(Client::class)),
        );
    }

    public function testThrowWhenInvalidExchangeSet()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Set<Innmind\AMQP\Model\Exchange\Declaration>');

        new AutoDeclare(
            $this->createMock(Client::class),
            Set::of('string'),
        );
    }

    public function testThrowWhenInvalidQueueSet()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type Set<Innmind\AMQP\Model\Queue\Declaration>');

        new AutoDeclare(
            $this->createMock(Client::class),
            null,
            Set::of('string'),
        );
    }

    public function testThrowWhenInvalidBindingSet()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 4 must be of type Set<Innmind\AMQP\Model\Queue\Binding>');

        new AutoDeclare(
            $this->createMock(Client::class),
            null,
            null,
            Set::of('string'),
        );
    }

    public function testClosed()
    {
        $client = new AutoDeclare(
            $mock = $this->createMock(Client::class),
        );
        $mock
            ->expects($this->exactly(2))
            ->method('closed')
            ->will($this->onConsecutiveCalls(false, true));

        $this->assertFalse($client->closed());
        $this->assertTrue($client->closed());
    }

    public function testDeclareModelOnChannelAccess()
    {
        $exchange = Exchange::temporary('foo', Type::direct);
        $exchange2 = clone $exchange;
        $queue = Queue::durable();
        $queue2 = clone $queue;
        $binding = new Binding('foo', 'bar');
        $binding2 = clone $binding;

        $client = new AutoDeclare(
            $mock = $this->createMock(Client::class),
            Set::of(Exchange::class, $exchange, $exchange2),
            Set::of(Queue::class, $queue, $queue2),
            Set::of(Binding::class, $binding, $binding2),
        );
        $mock
            ->expects($this->exactly(2))
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $channel
            ->expects($this->once())
            ->method('exchange')
            ->willReturn($clientExchange = $this->createMock(Channel\Exchange::class));
        $channel
            ->expects($this->once())
            ->method('queue')
            ->willReturn($clientQueue = $this->createMock(Channel\Queue::class));
        $clientExchange
            ->expects($this->exactly(2))
            ->method('declare')
            ->withConsecutive([$exchange], [$exchange2]);
        $clientQueue
            ->expects($this->exactly(2))
            ->method('declare')
            ->withConsecutive([$queue], [$queue2]);
        $clientQueue
            ->expects($this->exactly(2))
            ->method('bind')
            ->withConsecutive([$binding], [$binding2]);

        $this->assertSame($channel, $client->channel());
        //assert that the declaration is done only once
        $this->assertSame($channel, $client->channel());
    }

    public function testNoDeclarationDoneOnceClientClosed()
    {
        $exchange = Exchange::temporary('foo', Type::direct);
        $queue = Queue::durable();
        $binding = new Binding('foo', 'bar');

        $client = new AutoDeclare(
            $mock = $this->createMock(Client::class),
            Set::of(Exchange::class, $exchange),
            Set::of(Queue::class, $queue),
            Set::of(Binding::class, $binding),
        );
        $mock
            ->expects($this->once())
            ->method('close');
        $mock
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $channel
            ->expects($this->never())
            ->method('exchange');
        $channel
            ->expects($this->never())
            ->method('queue');

        $this->assertNull($client->close());
        $this->assertSame($channel, $client->channel());
    }
}
