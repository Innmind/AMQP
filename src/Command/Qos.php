<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Client\State,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Basic\Qos as Model,
};
use Innmind\Immutable\Attempt;

final class Qos implements Command
{
    private Model $command;

    private function __construct(Model $command)
    {
        $this->command = $command;
    }

    #[\Override]
    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
    ): Attempt {
        return $connection
            ->request(
                fn($protocol) => $protocol->basic()->qos(
                    $channel,
                    $this->command,
                ),
                Method::basicQosOk,
            )
            ->map(static fn() => $state)
            ->attempt(static fn() => Failure::toAdjustQos());
    }

    /**
     * @param int<0, 65535> $count The number of messages to prefetch for consumers
     */
    #[\NoDiscard]
    public static function of(int $count): self
    {
        // the size is not exposed as RabbitMQ doesn't support it
        return new self(Model::of(0, $count));
    }
}
