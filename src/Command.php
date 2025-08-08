<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Transport\{
    Connection,
    Frame\Channel,
    Connection\MessageReader,
};
use Innmind\Immutable\Either;

interface Command
{
    /**
     * @return Either<Failure, Client\State>
     */
    #[\NoDiscard]
    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        Client\State $state,
    ): Either;
}
