<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

/**
 * @psalm-immutable
 */
final class ToRollback
{
    /**
     * @internal
     */
    public function __construct()
    {
    }

    #[\NoDiscard]
    public function kind(): Kind
    {
        return Kind::toRollback;
    }
}
