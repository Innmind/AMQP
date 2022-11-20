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
 */
interface Command
{
    /**
     * @param I $state
     *
     * @return Either<Failure, array{Connection, O}>
     */
    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either;
}
