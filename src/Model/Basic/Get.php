<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

/**
 * @psalm-immutable
 */
final class Get
{
    private string $queue;
    private bool $ack = true;

    private function __construct(string $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(string $queue): self
    {
        return new self($queue);
    }

    #[\NoDiscard]
    public function manualAcknowledge(): self
    {
        $self = clone $this;
        $self->ack = true;

        return $self;
    }

    #[\NoDiscard]
    public function autoAcknowledge(): self
    {
        $self = clone $this;
        $self->ack = false;

        return $self;
    }

    #[\NoDiscard]
    public function queue(): string
    {
        return $this->queue;
    }

    #[\NoDiscard]
    public function shouldAutoAcknowledge(): bool
    {
        return !$this->ack;
    }
}
