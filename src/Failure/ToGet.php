<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Failure;

use Innmind\AMQP\{
    Failure,
    Model\Basic\Get as Command,
};

/**
 * @psalm-immutable
 */
final class ToGet extends Failure
{
    private Command $command;

    /**
     * @internal
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function command(): Command
    {
        return $this->command;
    }

    public function kind(): Kind
    {
        return Kind::toGet;
    }
}
