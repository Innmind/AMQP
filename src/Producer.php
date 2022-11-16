<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Model\Basic\{
    Publish,
    Message,
};

final class Producer
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

    /**
     * @return callable(string): self
     */
    public static function prepare(Client $client): callable
    {
        return static fn(string $exchange) => new self($client, $exchange);
    }
}
