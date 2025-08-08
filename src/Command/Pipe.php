<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame\Channel,
    Client\State,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class Pipe implements Command
{
    public function __construct(
        private Command $first,
        private Command $second,
    ) {
    }

    #[\Override]
    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
    ): Attempt {
        return ($this->first)($connection, $channel, $read, $state)->flatMap(
            fn($state) => ($this->second)(
                $connection,
                $channel,
                $read,
                $state,
            ),
        );
    }
}
