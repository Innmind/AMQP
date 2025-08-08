<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Client\State,
    Model\Queue\Deletion,
    Model\Queue\DeleteOk,
    Model\Count,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Protocol,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Failure,
};
use Innmind\Immutable\{
    Either,
    Sequence,
    Predicate\Instance,
};

final class DeleteQueue implements Command
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
        State $state,
    ): Either {
        $frames = fn(Protocol $protocol): Sequence => $protocol->queue()->delete(
            $channel,
            $this->command,
        );

        $sideEffect = match ($this->command->shouldWait()) {
            true => $connection
                ->request($frames, Method::queueDeleteOk)
                ->flatMap(
                    // this is here just to make sure the response is valid
                    // maybe in the future we could expose this info to the user
                    static fn($frame) => $frame
                        ->values()
                        ->first()
                        ->keep(Instance::of(Value\UnsignedLongInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->map(Count::of(...))
                        ->map(DeleteOk::of(...))
                        ->either(),
                ),
            false => $connection->send($frames),
        };

        return $sideEffect
            ->map(static fn() => $state)
            ->leftMap(fn() => Failure::toDeleteQueue($this->command));
    }

    #[\NoDiscard]
    public static function of(string $name): self
    {
        return new self(Deletion::of($name));
    }
}
