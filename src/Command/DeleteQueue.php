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
        /** @var Either<Failure, State> */
        return $connection
            ->send(fn($protocol) => $protocol->queue()->delete(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::queueDeleteOk)
            ->then(
                // this is here just to make sure the response is valid maybe in
                // the future we could expose this info to the user
                static fn($connection, $frame) => $frame
                    ->values()
                    ->first()
                    ->keep(Instance::of(Value\UnsignedLongInteger::class))
                    ->map(static fn($value) => $value->original())
                    ->map(Count::of(...))
                    ->map(DeleteOk::of(...))
                    ->map(static fn() => State::of($connection, $state)),
                static fn($connection) => State::of($connection, $state),
            )
            ->either()
            ->leftMap(static fn() => Failure::toDeleteQueue);
    }

    public static function of(string $name): self
    {
        return new self(Deletion::of($name));
    }
}
