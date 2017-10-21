<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\Client as ClientInterface;

final class SignalAware implements ClientInterface
{
    private $client;
    private $handlersRegistered = false;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
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

        pcntl_async_signals(true);

        $softClose = function(): void {
            $this->close();
        };

        pcntl_signal(SIGHUP, static function() {
            //do nothing so it can run in background
        });
        pcntl_signal(SIGINT, $softClose);
        pcntl_signal(SIGABRT, $softClose);
        pcntl_signal(SIGTERM, $softClose);
        pcntl_signal(SIGTSTP, $softClose);
        pcntl_signal(SIGALRM, $softClose);
        $this->handlersRegistered = true;
    }
}
