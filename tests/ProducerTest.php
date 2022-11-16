<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP;

use Innmind\AMQP\{
    Producer,
    Client,
    Client\Channel,
    Model\Basic\Message,
};
use PHPUnit\Framework\TestCase;

class ProducerTest extends TestCase
{
    public function testSendMessage()
    {
        $producer = new Producer(
            $client = $this->createMock(Client::class),
            'foo',
        );
        $message = $this->createMock(Message::class);
        $client
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $channel
            ->expects($this->once())
            ->method('basic')
            ->willReturn($basic = $this->createMock(Channel\Basic::class));
        $basic
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function($publish) use ($message): bool {
                return $publish->message() === $message &&
                    $publish->exchange() === 'foo' &&
                    $publish->routingKey() === '';
            }));

        $this->assertNull($producer($message));
    }

    public function testSendMessageWithRoutingKey()
    {
        $producer = new Producer(
            $client = $this->createMock(Client::class),
            'foo',
        );
        $message = $this->createMock(Message::class);
        $client
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel = $this->createMock(Channel::class));
        $channel
            ->expects($this->once())
            ->method('basic')
            ->willReturn($basic = $this->createMock(Channel\Basic::class));
        $basic
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function($publish) use ($message): bool {
                return $publish->message() === $message &&
                    $publish->exchange() === 'foo' &&
                    $publish->routingKey() === 'bar';
            }));

        $this->assertNull($producer($message, 'bar'));
    }
}
