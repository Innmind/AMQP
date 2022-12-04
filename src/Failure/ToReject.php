<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Failure;

/**
 * @psalm-immutable
 */
final class ToReject extends Failure
{
    private string $queue;

    public function __construct(string $queue)
    {
        $this->queue = $queue;
    }

    public function kind(): Kind
    {
        return Kind::toReject;
    }

    public function queue(): string
    {
        return $this->queue;
    }
}
