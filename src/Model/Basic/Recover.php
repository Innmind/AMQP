<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

final class Recover
{
    private bool $requeue = false;

    /**
     * This will requeue unacknowledged messages meaning they may be delivered
     * to a different consumer that the original one
     */
    public static function requeue(): self
    {
        $self = new self;
        $self->requeue = true;

        return $self;
    }

    public function shouldRequeue(): bool
    {
        return $this->requeue;
    }
}
