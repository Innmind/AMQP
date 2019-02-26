<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client\SignalAware,
    Client\Channel,
    Client,
};
use Innmind\OperatingSystem\CurrentProcess\Signals;
use Innmind\Signals\Signal;
use PHPUnit\Framework\TestCase;

class SignalAwareTest extends TestCase
{
    public function testInterface()
    {
        $client = new SignalAware(
            $inner = $this->createMock(Client::class),
            $signals = $this->createMock(Signals::class)
        );
        $inner
            ->expects($this->at(1))
            ->method('closed')
            ->willReturn(false);
        $inner
            ->expects($this->at(2))
            ->method('close');
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
        $listeners = [];
        $signals
            ->expects($this->at(0))
            ->method('listen')
            ->with(
                Signal::hangup(),
                $this->callback(static function($listen): bool {
                    $listen(); // doesn't expect to do anything

                    return true;
                })
            );
        $signals
            ->expects($this->at(1))
            ->method('listen')
            ->with(
                Signal::interrupt(),
                $this->callback(static function($listen) use (&$listeners): bool {
                    $listeners[] = $listen;

                    return true;
                })
            );
        $signals
            ->expects($this->at(2))
            ->method('listen')
            ->with(
                Signal::abort(),
                $this->callback(static function($listen) use (&$listeners): bool {
                    $listeners[] = $listen;

                    return true;
                })
            );
        $signals
            ->expects($this->at(3))
            ->method('listen')
            ->with(
                Signal::terminate(),
                $this->callback(static function($listen) use (&$listeners): bool {
                    $listeners[] = $listen;

                    return true;
                })
            );
        $signals
            ->expects($this->at(4))
            ->method('listen')
            ->with(
                Signal::terminalStop(),
                $this->callback(static function($listen) use (&$listeners): bool {
                    $listeners[] = $listen;

                    return true;
                })
            );
        $signals
            ->expects($this->at(5))
            ->method('listen')
            ->with(
                Signal::alarm(),
                $this->callback(static function($listen) use (&$listeners): bool {
                    $listeners[] = $listen;

                    return true;
                })
            );

        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(Channel::class, $client->channel());
        $this->assertFalse($client->closed());
        $this->assertNull($client->close());
        $this->assertTrue($client->closed());
        $this->assertCount( // verify it's the same listener to close the channel
            1,
            array_reduce(
                $listeners,
                static function(array $listeners, callable $listener): array {
                    if (in_array($listener, $listeners, true)) {
                        return $listeners;
                    }

                    $listeners[] = $listener;

                    return $listeners;
                },
                []
            )
        );
        array_walk($listeners, 'call_user_func');
    }
}
