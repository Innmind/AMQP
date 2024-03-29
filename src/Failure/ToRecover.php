<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Failure;

/**
 * @psalm-immutable
 */
final class ToRecover extends Failure
{
    private string $queue;

    /**
     * @internal
     */
    public function __construct(string $queue)
    {
        $this->queue = $queue;
    }

    public function kind(): Kind
    {
        return Kind::toRecover;
    }

    public function queue(): string
    {
        return $this->queue;
    }
}
