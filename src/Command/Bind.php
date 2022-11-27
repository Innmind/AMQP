<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Client\State,
    Failure,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Queue\Binding,
};
use Innmind\Immutable\Either;

final class Bind implements Command
{
    private Binding $command;

    private function __construct(Binding $command)
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
            ->send(fn($protocol) => $protocol->queue()->bind(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::queueBindOk)
            ->either()
            ->map(static fn($connection) => State::of($connection, $state))
            ->leftMap(static fn() => Failure::toBind);
    }

    public static function of(
        string $exchange,
        string $queue,
        string $routingKey = '',
    ): self {
        return new self(Binding::of($exchange, $queue, $routingKey));
    }

    public function withArgument(string $key, mixed $value): self
    {
        return new self($this->command->withArgument($key, $value));
    }
}
