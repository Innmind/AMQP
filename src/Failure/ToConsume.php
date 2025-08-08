<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\{
    Failure,
    Model\Basic\Consume as Command,
};

/**
 * @psalm-immutable
 */
final class ToConsume extends Failure
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
        return Kind::toConsume;
    }
}
