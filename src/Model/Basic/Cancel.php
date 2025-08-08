<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

/**
 * @psalm-immutable
 */
final class Cancel
{
    private string $consumerTag;
    private bool $wait = true;

    private function __construct(string $consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(string $consumerTag): self
    {
        return new self($consumerTag);
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
    public function consumerTag(): string
    {
        return $this->consumerTag;
    }

    #[\NoDiscard]
    public function shouldWait(): bool
    {
        return $this->wait;
    }
}
