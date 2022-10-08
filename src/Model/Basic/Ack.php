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
    /** @var int<0, max> */
    private int $deliveryTag;
    private bool $multiple = false;

    /**
     * @param int<0, max> $deliveryTag
     */
    public function __construct(int $deliveryTag)
    {
        $this->deliveryTag = $deliveryTag;
    }

    /**
     * @psalm-pure
     *
     * @param int<0, max> $deliveryTag
     */
    public static function multiple(int $deliveryTag): self
    {
        $self = new self($deliveryTag);
        $self->multiple = true;

        return $self;
    }

    /**
     * @return int<0, max>
     */
    public function deliveryTag(): int
    {
        return $this->deliveryTag;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }
}
