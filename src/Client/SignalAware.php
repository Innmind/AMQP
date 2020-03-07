<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\Client as ClientInterface;
use Innmind\OperatingSystem\CurrentProcess\Signals;
use Innmind\Signals\Signal;

final class SignalAware implements ClientInterface
{
    private ClientInterface $client;
    private Signals $signals;
    private bool $handlersRegistered = false;

    public function __construct(ClientInterface $client, Signals $signals)
    {
        $this->client = $client;
        $this->signals = $signals;
    }

    public function channel(): Channel
    {
        $this->register();

        return $this->client->channel();
    }

    public function closed(): bool
    {
        return $this->client->closed();
    }

    public function close(): void
    {
        $this->client->close();
    }

    private function register(): void
    {
        if ($this->handlersRegistered === true) {
            return;
        }

        $softClose = function(): void {
            $this->close();
        };

        $this->signals->listen(Signal::hangup(), static function() {
            // do nothing so it can run in background
        });
        $this->signals->listen(Signal::interrupt(), $softClose);
        $this->signals->listen(Signal::abort(), $softClose);
        $this->signals->listen(Signal::terminate(), $softClose);
        $this->signals->listen(Signal::terminalStop(), $softClose);
        $this->signals->listen(Signal::alarm(), $softClose);
        $this->handlersRegistered = true;
    }
}
