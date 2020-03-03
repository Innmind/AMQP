<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Exception\AutoGeneratedConsumerTagRequireServerResponse;
use Innmind\Immutable\Map;

final class Consume
{
    private string $queue;
    private ?string $consumerTag = null;
    private bool $local = true;
    private bool $ack = true;
    private bool $exclusive = false;
    private bool $wait = true;
    private Map $arguments;

    public function __construct(string $queue)
    {
        $this->queue = $queue;
        $this->arguments = Map::of('string', 'mixed');
    }

    public function withConsumerTag(string $tag): self
    {
        $self = clone $this;
        $self->consumerTag = $tag;

        return $self;
    }

    /**
     * Let the server define the consumer tag
     */
    public function withAutoGeneratedConsumerTag(): self
    {
        $self = clone $this;
        $self->consumerTag = null;

        return $self;
    }

    /**
     * Means the server will not deliver messages to the consumer
     */
    public function noLocal(): self
    {
        $self = clone $this;
        $self->local = false;

        return $self;
    }

    public function local(): self
    {
        $self = clone $this;
        $self->local = true;

        return $self;
    }

    public function manualAcknowledge(): self
    {
        $self = clone $this;
        $self->ack = true;

        return $self;
    }

    public function autoAcknowledge(): self
    {
        $self = clone $this;
        $self->ack = false;

        return $self;
    }

    public function exclusive(): self
    {
        $self = clone $this;
        $self->exclusive = true;

        return $self;
    }

    public function notExclusive(): self
    {
        $self = clone $this;
        $self->exclusive = false;

        return $self;
    }

    public function dontWait(): self
    {
        if ($this->shouldAutoGenerateConsumerTag()) {
            throw new AutoGeneratedConsumerTagRequireServerResponse;
        }

        $self = clone $this;
        $self->wait = false;

        return $self;
    }

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

    public function queue(): string
    {
        return $this->queue;
    }

    public function shouldAutoGenerateConsumerTag(): bool
    {
        return !\is_string($this->consumerTag);
    }

    public function consumerTag(): string
    {
        return $this->consumerTag;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function shouldAutoAcknowledge(): bool
    {
        return !$this->ack;
    }

    public function isExclusive(): bool
    {
        return $this->exclusive;
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
