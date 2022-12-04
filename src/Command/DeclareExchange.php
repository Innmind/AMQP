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
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Failure,
};
use Innmind\Immutable\Either;

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
        mixed $state,
    ): Either {
        /** @var Either<Failure, State> */
        return $connection
            ->send(fn($protocol) => $protocol->exchange()->declare(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::exchangeDeclareOk)
            ->either()
            ->map(static fn($connection) => State::of($connection, $state))
            ->leftMap(static fn() => Failure::toDeclareExchange);
    }

    public static function of(string $name, Type $type): self
    {
        return new self(Declaration::durable($name, $type));
    }
}
