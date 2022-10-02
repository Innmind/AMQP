<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

/**
 * Acknowledge
 *
 * @psalm-immutable
 */
final class Ack
{
    private int $deliveryTag;
    private bool $multiple = false;

    public function __construct(int $deliveryTag)
    {
        $this->deliveryTag = $deliveryTag;
    }

    /**
     * @psalm-pure
     */
    public static function multiple(int $deliveryTag): self
    {
        $self = new self($deliveryTag);
        $self->multiple = true;

        return $self;
    }

    public function deliveryTag(): int
    {
        return $this->deliveryTag;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }
}
