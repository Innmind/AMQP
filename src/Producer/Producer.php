<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Producer;

use Innmind\AMQP\{
    Client,
    Producer as ProducerInterface,
    Model\Basic\Publish,
    Model\Basic\Message,
};

final class Producer implements ProducerInterface
{
    private Client $client;
    private string $exchange;

    public function __construct(Client $client, string $exchange)
    {
        $this->client = $client;
        $this->exchange = $exchange;
    }

    public function __invoke(Message $message, string $routingKey = null): void
    {
        $this
            ->client
            ->channel()
            ->basic()
            ->publish(
                Publish::a($message)
                    ->to($this->exchange)
                    ->withRoutingKey($routingKey ?? ''),
            );
    }
}
