<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

/**
 * @psalm-immutable
 */
final class Recover
{
    private bool $requeue = false;

    private function __construct(bool $requeue)
    {
        $this->requeue = $requeue;
    }

    /**
     * @psalm-pure
     */
    public static function withoutRequeue(): self
    {
        return new self(false);
    }

    /**
     * This will requeue unacknowledged messages meaning they may be delivered
     * to a different consumer that the original one
     *
     * @psalm-pure
     */
    public static function requeue(): self
    {
        return new self(true);
    }

    public function shouldRequeue(): bool
    {
        return $this->requeue;
    }
}
