<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Transport\{
    Connection,
    Frame\Channel,
};
use Innmind\Immutable\Either;

interface Command
{
    /**
     * @template T
     *
     * @param T $state
     *
     * @return Either<Failure, array{Connection, T}>
     */
    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either;
}
