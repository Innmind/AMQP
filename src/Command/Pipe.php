<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Client\State,
    Transport\Connection,
    Transport\Frame\Channel,
};
use Innmind\Immutable\Either;

/**
 * @internal
 */
final class Pipe implements Command
{
    private Command $first;
    private Command $second;

    public function __construct(Command $first, Command $second)
    {
        $this->first = $first;
        $this->second = $second;
    }

    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either {
        return ($this->first)($connection, $channel, $state)->flatMap(
            fn($state) => ($this->second)(
                $state->connection(),
                $channel,
                $state->userState(),
            ),
        );
    }
}
