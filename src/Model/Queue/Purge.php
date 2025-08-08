<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Queue;

/**
 * @psalm-immutable
 */
final class Purge
{
    private string $name;
    private bool $wait = true;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(string $name): self
    {
        return new self($name);
    }

    #[\NoDiscard]
    public function dontWait(): self
    {
        $self = clone $this;
        $self->wait = false;

        return $self;
    }

    #[\NoDiscard]
    public function wait(): self
    {
        $self = clone $this;
        $self->wait = true;

        return $self;
    }

    #[\NoDiscard]
    public function name(): string
    {
        return $this->name;
    }

    #[\NoDiscard]
    public function shouldWait(): bool
    {
        return $this->wait;
    }
}
