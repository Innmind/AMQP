<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

/**
 * @psalm-immutable
 */
final class Reject
{
    /** @var int<0, max> */
    private int $deliveryTag;
    private bool $requeue = false;

    /**
     * @param int<0, max> $deliveryTag
     */
    public function __construct(int $deliveryTag)
    {
        $this->deliveryTag = $deliveryTag;
    }

    /**
     * This will requeue unacknowledged messages meaning they may be delivered
     * to a different consumer that the original one
     *
     * @psalm-pure
     *
     * @param int<0, max> $deliveryTag
     */
    public static function requeue(int $deliveryTag): self
    {
        $self = new self($deliveryTag);
        $self->requeue = true;

        return $self;
    }

    /**
     * @return int<0, max>
     */
    public function deliveryTag(): int
    {
        return $this->deliveryTag;
    }

    public function shouldRequeue(): bool
    {
        return $this->requeue;
    }
}
