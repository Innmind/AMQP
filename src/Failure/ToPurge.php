<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\{
    Failure,
    Model\Queue\Purge as Command,
};

/**
 * @psalm-immutable
 */
final class ToPurge extends Failure
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

    #[\Override]
    public function kind(): Kind
    {
        return Kind::toPurge;
    }
}
