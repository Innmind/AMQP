<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\Immutable\Map;

/**
 * @psalm-immutable
 */
final class Binding
{
    private string $exchange;
    private string $queue;
    private string $routingKey;
    private bool $wait = true;
    /** @var Map<string, mixed> */
    private Map $arguments;

    public function __construct(string $exchange, string $queue, string $routingKey = '')
    {
        $this->exchange = $exchange;
        $this->queue = $queue;
        $this->routingKey = $routingKey;
        /** @var Map<string, mixed> */
        $this->arguments = Map::of();
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

    public function withArgument(string $key, mixed $value): self
    {
        $self = clone $this;
        $self->arguments = ($self->arguments)($key, $value);

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
     * @return Map<string, mixed>
     */
    public function arguments(): Map
    {
        return $this->arguments;
    }
}
