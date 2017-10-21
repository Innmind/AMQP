<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client\SignalAware,
    Client\Channel,
    Client
};
use PHPUnit\Framework\TestCase;

class SignalAwareTest extends TestCase
{
    public function testInterface()
    {
        $client = new SignalAware(
            $inner = $this->createMock(Client::class)
        );
        $inner
            ->expects($this->at(2))
            ->method('close');
        $inner
            ->expects($this->at(1))
            ->method('closed')
            ->willReturn(false);
        $inner
            ->expects($this->at(3))
            ->method('closed')
            ->willReturn(true);
        $inner
            ->expects($this->at(4))
            ->method('close');
        $inner
            ->expects($this->at(5))
            ->method('close');
        $inner
            ->expects($this->at(6))
            ->method('close');
        $inner
            ->expects($this->at(7))
            ->method('close');
        $inner
            ->expects($this->at(8))
            ->method('close');

        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(Channel::class, $client->channel());
        $this->assertFalse($client->closed());
        $this->assertNull($client->close());
        $this->assertTrue($client->closed());
        $this->assertTrue(posix_kill(getmypid(), SIGHUP));
        $this->assertTrue(posix_kill(getmypid(), SIGINT));
        $this->assertTrue(posix_kill(getmypid(), SIGABRT));
        $this->assertTrue(posix_kill(getmypid(), SIGTERM));
        $this->assertTrue(posix_kill(getmypid(), SIGTSTP));
        $this->assertTrue(posix_kill(getmypid(), SIGALRM));
    }
}
