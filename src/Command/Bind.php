<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Client\State,
    Failure,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Protocol,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Queue\Binding,
};
use Innmind\Immutable\{
    Either,
    Sequence,
};

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
        MessageReader $read,
        State $state,
    ): Either {
        $frames = fn(Protocol $protocol): Sequence => $protocol->queue()->bind(
            $channel,
            $this->command,
        );

        $sideEffect = match ($this->command->shouldWait()) {
            true => $connection->request($frames, Method::queueBindOk),
            false => $connection->send($frames),
        };

        return $sideEffect
            ->map(static fn() => $state)
            ->leftMap(fn() => Failure::toBind($this->command));
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
