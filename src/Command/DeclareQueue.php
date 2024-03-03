<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Client\State,
    Model\Queue\Declaration,
    Model\Queue\DeclareOk,
    Model\Count,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Protocol,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Failure,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    Sequence,
    Predicate\Instance,
};

final class DeclareQueue implements Command
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
        State $state,
    ): Either {
        $frames = fn(Protocol $protocol): Sequence => $protocol->queue()->declare(
            $channel,
            $this->command,
        );

        $sideEffect = match ($this->command->shouldWait()) {
            true => $connection
                ->request($frames, Method::queueDeclareOk)
                ->flatMap(static function($frame) {
                    $name = $frame
                        ->values()
                        ->first()
                        ->keep(Instance::of(Value\ShortString::class))
                        ->map(static fn($value) => $value->original()->toString());
                    $message = $frame
                        ->values()
                        ->get(1)
                        ->keep(Instance::of(Value\UnsignedLongInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->map(Count::of(...));
                    $consumer = $frame
                        ->values()
                        ->get(2)
                        ->keep(Instance::of(Value\UnsignedLongInteger::class))
                        ->map(static fn($value) => $value->original())
                        ->map(Count::of(...));

                    // this is here just to make sure the response is valid
                    // maybe in the future we could expose this info to the user
                    return Maybe::all($name, $message, $consumer)
                        ->map(DeclareOk::of(...))
                        ->either();
                }),
            false => $connection->send($frames),
        };

        return $sideEffect
            ->map(static fn() => $state)
            ->leftMap(fn() => Failure::toDeclareQueue($this->command));
    }

    public static function of(string $name): self
    {
        return new self(Declaration::durable()->withName($name));
    }

    public function withArgument(string $key, mixed $value): self
    {
        return new self($this->command->withArgument($key, $value));
    }
}
