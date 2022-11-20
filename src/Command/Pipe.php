<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Transport\Connection,
    Transport\Frame\Channel,
};
use Innmind\Immutable\Either;

/**
 * @internal
 * @template A
 * @template B
 * @template C
 * @implements Command<A, C>
 */
final class Pipe implements Command
{
    /** @var Command<A, B> */
    private Command $first;
    /** @var Command<B, C> */
    private Command $second;

    /**
     * @param Command<A, B> $first
     * @param Command<B, C> $second
     */
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
            function($in) use ($channel) {
                [$connection, $state] = $in;

                return ($this->second)($connection, $channel, $state);
            },
        );
    }
}
