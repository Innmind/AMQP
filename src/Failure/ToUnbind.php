<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Model\Queue\Unbinding as Command;

/**
 * @psalm-immutable
 */
final class ToUnbind
{
    /**
     * @internal
     */
    public function __construct(private Command $command)
    {
    }

    #[\NoDiscard]
    public function command(): Command
    {
        return $this->command;
    }
}
