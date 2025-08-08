<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

/**
 * @psalm-immutable
 */
final class ToAck
{
    private string $queue;

    /**
     * @internal
     */
    public function __construct(string $queue)
    {
        $this->queue = $queue;
    }

    #[\NoDiscard]
    public function kind(): Kind
    {
        return Kind::toAck;
    }

    #[\NoDiscard]
    public function queue(): string
    {
        return $this->queue;
    }
}
