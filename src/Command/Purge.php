<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Transport\Connection,
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

/**
 * @template S
 * @implements Command<S, S>
 */
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
        mixed $state,
    ): Either {
        /** @var Either<Failure, array{Connection, S}> */
        return $connection
            ->send(fn($protocol) => $protocol->queue()->purge(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::queuePurgeOk)
            ->match(
                static function($connection, $frame) use ($state) {
                    $purgeOk = $frame
                        ->values()
                        ->first()
                        ->keep(Instance::of(Value\UnsignedLongInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->map(Count::of(...))
                        ->map(PurgeOk::of(...));

                    // this is here just to make sure the response is valid
                    // maybe in the future we could expose this info to the user
                    return $purgeOk
                        ->either()
                        ->map(static fn() => [$connection, $state])
                        ->leftMap(static fn() => Failure::toPurge);
                },
                static fn($connection) => Either::right([$connection, $state]),
                static fn() => Either::left(Failure::toBind),
            );
    }

    public static function of(string $queue): self
    {
        return new self(Model::of($queue));
    }
}
