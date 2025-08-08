<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

/**
 * @psalm-immutable
 */
final class ToReject
{
    /**
     * @internal
     */
    public function __construct(private string $queue)
    {
    }

    #[\NoDiscard]
    public function queue(): string
    {
        return $this->queue;
    }
}
