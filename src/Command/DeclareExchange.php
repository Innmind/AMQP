<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Model\Exchange\Declaration,
    Model\Exchange\Type,
    Transport\Connection,
    Transport\Frame\Channel,
    Transport\Frame\Method,
};
use Innmind\Immutable\Either;

/**
 * @template S
 * @implements Command<S, S, \Error>
 */
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
        mixed $state,
    ): Either {
        return $connection
            ->send(fn($protocol) => $protocol->exchange()->declare(
                $channel,
                $this->command,
            ))
            ->maybeWait($this->command->shouldWait(), Method::exchangeDeclareOk)
            ->match(
                static fn($connection) => Either::right([$connection, $state]),
                static fn($connection) => Either::right([$connection, $state]),
                static fn() => Either::left(new \RuntimeException),
            );
    }

    public static function of(string $name, Type $type): self
    {
        return new self(Declaration::durable($name, $type));
    }
}
