<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Client\State,
    Model\Exchange\Declaration,
    Model\Exchange\Type,
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

final class DeclareExchange implements Command
{
    private Declaration $command;

    private function __construct(Declaration $command)
    {
        $this->command = $command;
    }

    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
    ): Either {
        $frames = fn(Protocol $protocol): Sequence => $protocol->exchange()->declare(
            $channel,
            $this->command,
        );

        $sideEffect = match ($this->command->shouldWait()) {
            true => $connection->request($frames, Method::exchangeDeclareOk),
            false => $connection->send($frames),
        };

        return $sideEffect
            ->map(static fn() => $state)
            ->leftMap(fn() => Failure::toDeclareExchange($this->command));
    }

    #[\NoDiscard]
    public static function of(string $name, Type $type): self
    {
        return new self(Declaration::durable($name, $type));
    }
}
