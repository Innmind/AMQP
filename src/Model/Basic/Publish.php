<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

/**
 * @psalm-immutable
 */
final class Publish
{
    private Message $message;
    private string $exchange = '';
    private string $routingKey = '';
    private bool $mandatory = false;
    private bool $immediate = false;

    private function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function a(Message $message): self
    {
        return new self($message);
    }

    #[\NoDiscard]
    public function to(string $exchange): self
    {
        $self = clone $this;
        $self->exchange = $exchange;

        return $self;
    }

    #[\NoDiscard]
    public function toDefaultExchange(): self
    {
        $self = clone $this;
        $self->exchange = '';

        return $self;
    }

    #[\NoDiscard]
    public function withRoutingKey(string $key): self
    {
        $self = clone $this;
        $self->routingKey = $key;

        return $self;
    }

    /**
     * This will raise an error if the message can't be routed
     */
    #[\NoDiscard]
    public function flagAsMandatory(): self
    {
        $self = clone $this;
        $self->mandatory = true;

        return $self;
    }

    /**
     * This will fail silently if the message can't be routed
     */
    #[\NoDiscard]
    public function flagAsNotMandatory(): self
    {
        $self = clone $this;
        $self->mandatory = false;

        return $self;
    }

    /**
     * This will raise an error if the message can't be delivered immediately
     */
    #[\NoDiscard]
    public function flagAsImmediate(): self
    {
        $self = clone $this;
        $self->immediate = true;

        return $self;
    }

    /**
     * This will fail silently if the message can't be delivered immediately
     */
    #[\NoDiscard]
    public function flagAsNotImmediate(): self
    {
        $self = clone $this;
        $self->immediate = false;

        return $self;
    }

    #[\NoDiscard]
    public function message(): Message
    {
        return $this->message;
    }

    #[\NoDiscard]
    public function exchange(): string
    {
        return $this->exchange;
    }

    #[\NoDiscard]
    public function routingKey(): string
    {
        return $this->routingKey;
    }

    #[\NoDiscard]
    public function mandatory(): bool
    {
        return $this->mandatory;
    }

    #[\NoDiscard]
    public function immediate(): bool
    {
        return $this->immediate;
    }
}
