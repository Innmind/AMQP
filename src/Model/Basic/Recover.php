<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

/**
 * @psalm-immutable
 */
final class Recover
{
    private function __construct(private bool $requeue)
    {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
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
    #[\NoDiscard]
    public static function requeue(): self
    {
        return new self(true);
    }

    #[\NoDiscard]
    public function shouldRequeue(): bool
    {
        return $this->requeue;
    }
}
