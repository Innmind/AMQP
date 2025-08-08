<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

use Innmind\Immutable\Map;

/**
 * @psalm-immutable
 */
final class Unbinding
{
    private string $exchange;
    private string $queue;
    private string $routingKey;
    /** @var Map<string, mixed> */
    private Map $arguments;

    private function __construct(string $exchange, string $queue, string $routingKey = '')
    {
        $this->exchange = $exchange;
        $this->queue = $queue;
        $this->routingKey = $routingKey;
        /** @var Map<string, mixed> */
        $this->arguments = Map::of();
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(string $exchange, string $queue, string $routingKey = ''): self
    {
        return new self($exchange, $queue, $routingKey);
    }

    #[\NoDiscard]
    public function withArgument(string $key, mixed $value): self
    {
        $self = clone $this;
        $self->arguments = ($self->arguments)($key, $value);

        return $self;
    }

    #[\NoDiscard]
    public function exchange(): string
    {
        return $this->exchange;
    }

    #[\NoDiscard]
    public function queue(): string
    {
        return $this->queue;
    }

    #[\NoDiscard]
    public function routingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * @return Map<string, mixed>
     */
    #[\NoDiscard]
    public function arguments(): Map
    {
        return $this->arguments;
    }
}
