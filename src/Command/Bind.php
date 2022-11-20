<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Queue\Binding,
};
use Innmind\Immutable\Either;

final class Bind implements Command
{
    private Binding $command;

    private function __construct(Binding $command)
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
            ->send(fn($protocol) => $protocol->queue()->bind(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::queueBindOk)
            ->match(
                static fn($connection) => Either::right([$connection, $state]),
                static fn($connection) => Either::right([$connection, $state]),
                static fn() => Either::left(Failure::toBind),
            );
    }

    public static function of(
        string $exchange,
        string $queue,
        string $routingKey = '',
    ): self {
        return new self(Binding::of($exchange, $queue, $routingKey));
    }

    public function withArgument(string $key, mixed $value): self
    {
        return new self($this->command->withArgument($key, $value));
    }
}
