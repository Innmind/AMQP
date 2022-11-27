<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Client\State,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Basic\Qos as Model,
};
use Innmind\Immutable\Either;

final class Qos implements Command
{
    private Model $command;

    private function __construct(Model $command)
    {
        $this->command = $command;
    }

    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either {
        /** @var Either<Failure, State> */
        return $connection
            ->send(fn($protocol) => $protocol->basic()->qos(
                $channel,
                $this->command,
            ))
            ->wait(Method::basicQosOk)
            ->match(
                static fn($connection) => Either::right(State::of($connection, $state)),
                static fn($connection) => Either::right(State::of($connection, $state)),
                static fn() => Either::left(Failure::toAdjustQos),
            );
    }

    /**
     * @param int<0, 65535> $count The number of messages to prefetch for consumers
     */
    public static function of(int $count): self
    {
        // the size is not exposed as RabbitMQ doesn't support it
        return new self(Model::of(0, $count));
    }
}
