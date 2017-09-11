<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Queue;

use Innmind\AMQP\{
    Client\Channel\Queue\NullQueue,
    Client\Channel\Queue,
    Model\Queue\Declaration,
    Model\Queue\Deletion,
    Model\Queue\Binding,
    Model\Queue\Unbinding,
    Model\Queue\Purge
};
use PHPUnit\Framework\TestCase;

class NullQueueTest extends TestCase
{
    public function testInterface()
    {
        $queue = new NullQueue;

        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertNull($queue->declare(Declaration::passive('')));
        $this->assertNull($queue->delete(new Deletion('')));
        $this->assertSame($queue, $queue->bind(new Binding('', '')));
        $this->assertSame($queue, $queue->unbind(new Unbinding('', '')));
        $this->assertNull($queue->purge(new Purge('')));
    }
}
