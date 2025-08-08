<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\Model\Queue\Unbinding as Command;

/**
 * @psalm-immutable
 */
final class ToUnbind
{
    private Command $command;

    /**
     * @internal
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    #[\NoDiscard]
    public function command(): Command
    {
        return $this->command;
    }
}
