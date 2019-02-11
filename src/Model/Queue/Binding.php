<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class Binding
{
    private $exchange;
    private $queue;
    private $routingKey;
    private $wait = true;
    private $arguments;

    public function __construct(string $exchange, string $queue, string $routingKey = '')
    {
        $this->exchange = $exchange;
        $this->queue = $queue;
        $this->routingKey = $routingKey;
        $this->arguments = new Map('string', 'mixed');
    }

    /**
     * Don't wait for the server response
     */
    public function dontWait(): self
    {
        $self = clone $this;
        $self->wait = false;

        return $self;
    }

    /**
     * Wait for the response server
     */
    public function wait(): self
    {
        $self = clone $this;
        $self->wait = true;

        return $self;
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

    public function shouldWait(): bool
    {
        return $this->wait;
    }

    /**
     * @return MapInterface<string, mixed>
     */
    public function arguments(): MapInterface
    {
        return $this->arguments;
    }
}
