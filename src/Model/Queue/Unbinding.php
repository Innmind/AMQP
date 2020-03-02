<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class Unbinding
{
    private string $exchange;
    private string $queue;
    private string $routingKey;
    private Map $arguments;

    public function __construct(string $exchange, string $queue, string $routingKey = '')
    {
        $this->exchange = $exchange;
        $this->queue = $queue;
        $this->routingKey = $routingKey;
        $this->arguments = new Map('string', 'mixed');
    }

    public function withArgument(string $key, $value): self
    {
        $self = clone $this;
        $self->arguments = $self->arguments->put($key, $value);

        return $self;
    }

    public function exchange(): string
    {
        return $this->exchange;
    }

    public function queue(): string
    {
        return $this->queue;
    }

    public function routingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * @return MapInterface<string, mixed>
     */
    public function arguments(): MapInterface
    {
        return $this->arguments;
    }
}
