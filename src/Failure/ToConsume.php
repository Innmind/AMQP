<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\{
    Model\Basic\Consume as Command,
};

/**
 * @psalm-immutable
 */
final class ToConsume
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

    #[\NoDiscard]
    public function kind(): Kind
    {
        return Kind::toConsume;
    }
}
