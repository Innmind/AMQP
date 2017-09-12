<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

use Innmind\AMQP\Client as ClientInterface;

final class Fluent implements ClientInterface
{
    private $client;
    private $closed = false;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function channel(): Channel
    {
        if ($this->closed()) {
            return new Channel\NullChannel;
        }

        return new Channel\Fluent($this->client->channel());
    }

    public function closed(): bool
    {
        return $this->closed || $this->client->closed();
    }

    public function close(): void
    {
        if ($this->closed()) {
            return;
        }

        $this->client->close();
        $this->closed = true;
    }
}
