<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Client\State,
    Model\Exchange\Deletion,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Protocol,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Failure,
};
use Innmind\Immutable\{
    Attempt,
    Sequence,
};

final class DeleteExchange implements Command
{
    private Deletion $command;

    private function __construct(Deletion $command)
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
        $frames = fn(Protocol $protocol): Sequence => $protocol->exchange()->delete(
            $channel,
            $this->command,
        );

        $sideEffect = match ($this->command->shouldWait()) {
            true => $connection->request($frames, Method::exchangeDeleteOk),
            false => $connection->send($frames),
        };

        return $sideEffect
            ->map(static fn() => $state)
            ->recover(fn() => Attempt::error(Failure::toDeleteExchange($this->command)));
    }

    #[\NoDiscard]
    public static function of(string $name): self
    {
        return new self(Deletion::of($name));
    }
}
