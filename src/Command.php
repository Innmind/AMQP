<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Transport\{
    Connection,
    Frame\Channel,
};
use Innmind\Immutable\Either;

/**
 * @template I
 * @template O
 * @template E
 */
interface Command
{
    /**
     * @param I $state
     *
     * @return Either<E, array{Connection, O}>
     */
    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either;
}
