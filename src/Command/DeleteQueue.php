<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Model\Queue\Deletion,
    Model\Queue\DeleteOk,
    Model\Count,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Failure,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    Predicate\Instance,
};

/**
 * @template S
 * @implements Command<S, S>
 */
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
        mixed $state,
    ): Either {
        /** @var Either<Failure, array{Connection, S}> */
        return $connection
            ->send(fn($protocol) => $protocol->queue()->delete(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::queueDeleteOk)
            ->match(
                static function($connection, $frame) use ($state) {
                    $deleteOk = $frame
                        ->values()
                        ->first()
                        ->keep(Instance::of(Value\UnsignedLongInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->map(Count::of(...))
                        ->map(DeleteOk::of(...));

                    // this is here just to make sure the response is valid
                    // maybe in the future we could expose this info to the user
                    return $deleteOk
                        ->either()
                        ->map(static fn() => [$connection, $state])
                        ->leftMap(static fn() => Failure::toDeleteQueue);
                },
                static fn($connection) => Either::right([$connection, $state]),
                static fn() => Either::left(Failure::toDeleteQueue),
            );
    }

    public static function of(string $name): self
    {
        return new self(Deletion::of($name));
    }
}
