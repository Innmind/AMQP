<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Failure;
use Innmind\Signals\Signal;

/**
 * @psalm-immutable
 */
final class ClosedBySignal extends Failure
{
    private Signal $signal;

    /**
     * @internal
     */
    public function __construct(Signal $signal)
    {
        $this->signal = $signal;
    }

    public function kind(): Kind
    {
        return Kind::closedBySignal;
    }

    #[\NoDiscard]
    public function signal(): Signal
    {
        return $this->signal;
    }
}
