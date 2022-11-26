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
     * @return Either<Failure, Client\State>
     */
    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either;
}
