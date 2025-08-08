<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Model\Queue\Deletion as Command;

/**
 * @psalm-immutable
 */
final class ToDeleteQueue
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
