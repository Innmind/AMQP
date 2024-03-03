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
    Either,
    Sequence,
};

final class DeleteExchange implements Command
{
    private Deletion $command;

    private function __construct(Deletion $command)
    {
        $this->command = $command;
    }

    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        mixed $state,
    ): Either {
        $frames = fn(Protocol $protocol): Sequence => $protocol->exchange()->delete(
            $channel,
            $this->command,
        );

        $sideEffect = match ($this->command->shouldWait()) {
            true => $connection->request($frames, Method::exchangeDeleteOk),
            false => $connection->tell($frames),
        };

        return $sideEffect
            ->map(static fn() => State::of($connection, $state))
            ->leftMap(fn() => Failure::toDeleteExchange($this->command));
    }

    public static function of(string $name): self
    {
        return new self(Deletion::of($name));
    }
}
