<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client\Fluent,
    Client\Channel,
    Client,
};
use PHPUnit\Framework\TestCase;

class FluentTest extends TestCase
{
    public function testInterface()
    {
        $fluent = new Fluent(
            $client = $this->createMock(Client::class),
        );
        $client
            ->expects($this->once())
            ->method('close');
        $client
            ->method('closed')
            ->willReturn(false);

        $this->assertInstanceOf(Client::class, $fluent);
        $this->assertInstanceOf(Channel\Fluent::class, $fluent->channel());
        $this->assertFalse($fluent->closed());
        $this->assertNull($fluent->close());
        $this->assertInstanceOf(Channel\NullChannel::class, $fluent->channel());
        $this->assertTrue($fluent->closed());
        $this->assertNull($fluent->close()); //ensure nothing happens
    }
}
