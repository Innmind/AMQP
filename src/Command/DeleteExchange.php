<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
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

    /**
     * @template S
     *
     * @param S $state
     *
     * @return Either<Failure, array{Connection, S}>
     */
    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either {
        /** @var Either<Failure, array{Connection, S}> */
        return $connection
            ->send(fn($protocol) => $protocol->exchange()->delete(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::exchangeDeleteOk)
            ->match(
                static fn($connection) => Either::right([$connection, $state]),
                static fn($connection) => Either::right([$connection, $state]),
                static fn() => Either::left(Failure::toDeleteExchange),
            );
    }

    public static function of(string $name): self
    {
        return new self(Deletion::of($name));
    }
}
