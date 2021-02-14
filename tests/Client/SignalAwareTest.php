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
            ->expects($this->exactly(2))
            ->method('closed')
            ->will($this->onConsecutiveCalls(false, true));
        $inner
            ->expects($this->exactly(6))
            ->method('close');
        $listeners = [];
        $callback = $this->callback(static function($listen) use (&$listeners): bool {
            $listeners[] = $listen;

            return true;
        });

        $signals
            ->expects($this->exactly(6))
            ->method('listen')
            ->withConsecutive(
                [
                    Signal::hangup(),
                    $this->callback(static function($listen): bool {
                        $listen(); // doesn't expect to do anything

                        return true;
                    }),
                ],
                [Signal::interrupt(), $callback],
                [Signal::abort(), $callback],
                [Signal::terminate(), $callback],
                [Signal::terminalStop(), $callback],
                [Signal::alarm(), $callback],
            );

        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(Channel::class, $client->channel());
        $this->assertFalse($client->closed());
        $this->assertNull($client->close());
        $this->assertTrue($client->closed());
        $this->assertCount( // verify it's the same listener to close the channel
            1,
            \array_reduce(
                $listeners,
                static function(array $listeners, callable $listener): array {
                    if (\in_array($listener, $listeners, true)) {
                        return $listeners;
                    }

                    $listeners[] = $listener;

                    return $listeners;
                },
                []
            )
        );
        \array_walk($listeners, 'call_user_func');
    }
}
