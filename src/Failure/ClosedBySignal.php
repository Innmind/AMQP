<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\Signals\Signal;

/**
 * @psalm-immutable
 */
final class ClosedBySignal
{
    /**
     * @internal
     */
    public function __construct(private Signal $signal)
    {
    }

    #[\NoDiscard]
    public function signal(): Signal
    {
        return $this->signal;
    }
}
