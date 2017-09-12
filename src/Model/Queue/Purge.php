<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

final class Purge
{
    private $name;
    private $wait = true;

    public function __construct(string $name)
    {
        $this->name = $name;
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

    public function name(): string
    {
        return $this->name;
    }

    public function shouldWait(): bool
    {
        return $this->wait;
    }
}
