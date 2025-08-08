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
    Attempt,
    Sequence,
};

final class DeclareExchange implements Command
{
    private function __construct(private Declaration $command)
    {
    }

    #[\Override]
    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
    ): Attempt {
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
            ->mapError(Failure::as(Failure::toDeclareExchange($this->command)));
    }

    #[\NoDiscard]
    public static function of(string $name, Type $type): self
    {
        return new self(Declaration::durable($name, $type));
    }
}
