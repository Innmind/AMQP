<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

final class Cancel
{
    private string $consumerTag;
    private bool $wait = true;

    public function __construct(string $consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    public function dontWait(): self
    {
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

    public function consumerTag(): string
    {
        return $this->consumerTag;
    }

    public function shouldWait(): bool
    {
        return $this->wait;
    }
}
