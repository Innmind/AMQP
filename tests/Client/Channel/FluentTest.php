<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel;

use Innmind\AMQP\Client\{
    Channel\Fluent,
    Channel,
    Channel\Exchange,
    Channel\Queue,
    Channel\Basic,
    Channel\Transaction,
};
use PHPUnit\Framework\TestCase;

class FluentTest extends TestCase
{
    public function testInterface()
    {
        $fluent = new Fluent(
            $channel = $this->createMock(Channel::class)
        );
        $channel
            ->expects($this->once())
            ->method('closed')
            ->willReturn(false);
        $channel
            ->expects($this->once())
            ->method('close');

        $this->assertInstanceOf(Channel::class, $fluent);
        $this->assertInstanceOf(Exchange::class, $fluent->exchange());
        $this->assertInstanceOf(Queue::class, $fluent->queue());
        $this->assertInstanceOf(Basic::class, $fluent->basic());
        $this->assertInstanceOf(Transaction::class, $fluent->transaction());
        $this->assertFalse($fluent->closed());
        $this->assertNull($fluent->close());
        $this->assertInstanceOf(Exchange\NullExchange::class, $fluent->exchange());
        $this->assertInstanceOf(Queue\NullQueue::class, $fluent->queue());
        $this->assertInstanceOf(Basic\NullBasic::class, $fluent->basic());
        $this->assertInstanceOf(Transaction\NullTransaction::class, $fluent->transaction());
        $this->assertTrue($fluent->closed());
        $this->assertNull($fluent->close()); //ensure nothing happens
    }
}
