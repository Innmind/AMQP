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
    Transport\Frame\Value,
    Model\Queue\Purge as Model,
    Model\Queue\PurgeOk,
    Model\Count,
};
use Innmind\Immutable\{
    Either,
    Predicate\Instance,
};

final class Purge implements Command
{
    private Model $command;

    private function __construct(Model $command)
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
            ->send(fn($protocol) => $protocol->queue()->purge(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::queuePurgeOk)
            ->then(
                // this is here just to make sure the response is valid maybe in
                // the future we could expose this info to the user
                static fn($connection, $frame) => $frame
                    ->values()
                    ->first()
                    ->keep(Instance::of(Value\UnsignedLongInteger::class))
                    ->map(static fn($value) => $value->original())
                    ->map(Count::of(...))
                    ->map(PurgeOk::of(...))
                    ->map(static fn() => State::of($connection, $state)),
                static fn($connection) => State::of($connection, $state),
            )
            ->either()
            ->leftMap(fn() => Failure::toPurge($this->command));
    }

    public static function of(string $queue): self
    {
        return new self(Model::of($queue));
    }
}
