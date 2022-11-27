<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Client\State,
    Model\Exchange\Deletion,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Failure,
};
use Innmind\Immutable\Either;

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
        mixed $state,
    ): Either {
        /** @var Either<Failure, State> */
        return $connection
            ->send(fn($protocol) => $protocol->exchange()->delete(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::exchangeDeleteOk)
            ->either()
            ->map(static fn($connection) => State::of($connection, $state))
            ->leftMap(static fn() => Failure::toDeleteExchange);
    }

    public static function of(string $name): self
    {
        return new self(Deletion::of($name));
    }
}
