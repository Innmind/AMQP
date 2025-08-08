<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Client\State,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Protocol,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Model\Queue\Purge as Model,
    Model\Queue\PurgeOk,
    Model\Count,
};
use Innmind\Immutable\{
    Attempt,
    Sequence,
    Predicate\Instance,
};

final class Purge implements Command
{
    private function __construct(private Model $command)
    {
    }

    #[\Override]
    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
    ): Attempt {
        $frames = fn(Protocol $protocol): Sequence => $protocol->queue()->purge(
            $channel,
            $this->command,
        );

        $sideEffect = match ($this->command->shouldWait()) {
            true => $connection
                ->request($frames, Method::queuePurgeOk)
                ->flatMap(
                    // this is here just to make sure the response is valid
                    // maybe in the future we could expose this info to the user
                    static fn($frame) => $frame
                        ->values()
                        ->first()
                        ->keep(Instance::of(Value\UnsignedLongInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->map(Count::of(...))
                        ->map(PurgeOk::of(...))
                        ->attempt(static fn() => new \RuntimeException('Unable to find message count')),
                ),
            false => $connection->send($frames),
        };

        return $sideEffect
            ->map(static fn() => $state)
            ->mapError(Failure::as(Failure::toPurge($this->command)));
    }

    #[\NoDiscard]
    public static function of(string $queue): self
    {
        return new self(Model::of($queue));
    }
}
