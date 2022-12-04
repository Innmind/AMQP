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
    Model\Queue\Unbinding,
};
use Innmind\Immutable\Either;

final class Unbind implements Command
{
    private Unbinding $command;

    private function __construct(Unbinding $command)
    {
        $this->command = $command;
    }

    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        mixed $state,
    ): Either {
        /** @var Either<Failure, State> */
        return $connection
            ->send(fn($protocol) => $protocol->queue()->unbind(
                $channel,
                $this->command,
            ))
            ->wait(Method::queueUnbindOk)
            ->either()
            ->map(static fn($connection) => State::of($connection, $state))
            ->leftMap(fn() => Failure::toUnbind($this->command));
    }

    public static function of(
        string $exchange,
        string $queue,
        string $routingKey = '',
    ): self {
        return new self(Unbinding::of($exchange, $queue, $routingKey));
    }

    public function withArgument(string $key, mixed $value): self
    {
        return new self($this->command->withArgument($key, $value));
    }
}
