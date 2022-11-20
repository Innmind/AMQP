<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Model\Queue\Unbinding,
};
use Innmind\Immutable\Either;

final class Unbind implements Command
{
    private Unbinding $command;

    private function __construct(Unbinding $command)
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
            ->send(fn($protocol) => $protocol->queue()->unbind(
                $channel,
                $this->command,
            ))
            ->wait(Method::queueUnbindOk)
            ->match(
                static fn($connection) => Either::right([$connection, $state]),
                static fn($connection) => Either::right([$connection, $state]),
                static fn() => Either::left(Failure::toUnbind),
            );
    }

    public static function of(
        string $exchange,
        string $queue,
        string $routingKey = '',
    ): self {
        return new self(Unbinding::of($exchange, $queue, $routingKey));
    }

    public function withArgument(string $key, mixed $value): self
    {
        return new self($this->command->withArgument($key, $value));
    }
}
