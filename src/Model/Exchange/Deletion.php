<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Exchange;

/**
 * @psalm-immutable
 */
final class Deletion
{
    private string $name;
    private bool $ifUnused = false;
    private bool $wait = true;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @psalm-pure
     */
    public static function of(string $name): self
    {
        return new self($name);
    }

    public function ifUnused(): self
    {
        $self = clone $this;
        $self->ifUnused = true;

        return $self;
    }

    public function ifUsed(): self
    {
        $self = clone $this;
        $self->ifUnused = false;

        return $self;
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

    public function onlyIfUnused(): bool
    {
        return $this->ifUnused;
    }

    public function shouldWait(): bool
    {
        return $this->wait;
    }
}
