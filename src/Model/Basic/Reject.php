<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

final class Reject
{
    private $deliveryTag;
    private $requeue = false;

    public function __construct(int $deliveryTag)
    {
        $this->deliveryTag = $deliveryTag;
    }

    /**
     * This will requeue unacknowledged messages meaning they may be delivered
     * to a different consumer that the original one
     */
    public static function requeue(int $deliveryTag): self
    {
        $self = new self($deliveryTag);
        $self->requeue = true;

        return $self;
    }

    public function deliveryTag(): int
    {
        return $this->deliveryTag;
    }

    public function shouldRequeue(): bool
    {
        return $this->requeue;
    }
}
