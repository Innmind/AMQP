<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

final class Publish
{
    private $message;
    private $exchange = '';
    private $routingKey = '';
    private $mandatory = false;
    private $immediate = false;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function to(string $exchange): self
    {
        $self = clone $this;
        $self->exchange = $exchange;

        return $self;
    }

    public function toDefaultExchange(): self
    {
        $self = clone $this;
        $self->exchange = '';

        return $self;
    }

    public function withRoutingKey(string $key): self
    {
        $self = clone $this;
        $self->routingKey = $key;

        return $self;
    }

    /**
     * This will raise an error if the message can't be routed
     */
    public function flagAsMandatory(): self
    {
        $self = clone $this;
        $self->mandatory = true;

        return $self;
    }

    /**
     * This will fail silently if the message can't be routed
     */
    public function flagAsNotMandatory(): self
    {
        $self = clone $this;
        $self->mandatory = false;

        return $self;
    }

    /**
     * This will raise an error if the message can't be delivered immediately
     */
    public function flagAsImmediate(): self
    {
        $self = clone $this;
        $self->immediate = true;

        return $self;
    }

    /**
     * This will fail silently if the message can't be delivered immediately
     */
    public function flagAsNotImmediate(): self
    {
        $self = clone $this;
        $self->immediate = false;

        return $self;
    }

    public function message(): Message
    {
        return $this->message;
    }

    public function exchange(): string
    {
        return $this->exchange;
    }

    public function routingKey(): string
    {
        return $this->routingKey;
    }

    public function mandatory(): bool
    {
        return $this->mandatory;
    }

    public function immediate(): bool
    {
        return $this->immediate;
    }
}
