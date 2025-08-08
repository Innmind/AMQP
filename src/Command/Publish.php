<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Client\State,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame\Channel,
    Model\Basic\Publish as Model,
    Model\Basic\Message,
};
use Innmind\Immutable\{
    Either,
    Sequence,
    SideEffect,
};

final class Publish implements Command
{
    /** @var Sequence<Model> */
    private Sequence $commands;

    /**
     * @param Sequence<Model> $commands
     */
    private function __construct(Sequence $commands)
    {
        $this->commands = $commands;
    }

    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
    ): Either {
        /** @var Either<Failure, State> */
        return $this
            ->commands
            ->reduce(
                Either::right(new SideEffect),
                fn(Either $state, $command) => $state->flatMap(
                    fn() => $this->publish($connection, $channel, $command),
                ),
            )
            ->map(static fn() => $state);
    }

    #[\NoDiscard]
    public static function one(Message $message): self
    {
        return new self(Sequence::of(Model::a($message)));
    }

    /**
     * @param Sequence<Message> $messages
     */
    #[\NoDiscard]
    public static function many(Sequence $messages): self
    {
        return new self($messages->map(Model::a(...)));
    }

    #[\NoDiscard]
    public function to(string $exchange): self
    {
        return new self($this->commands->map(
            static fn($publish) => $publish->to($exchange),
        ));
    }

    #[\NoDiscard]
    public function withRoutingKey(string $routingKey): self
    {
        return new self($this->commands->map(
            static fn($publish) => $publish->withRoutingKey($routingKey),
        ));
    }

    /**
     * @return Either<Failure, SideEffect>
     */
    private function publish(
        Connection $connection,
        Channel $channel,
        Model $command,
    ): Either {
        return $connection
            ->send(static fn($protocol, $maxFrameSize) => $protocol->basic()->publish(
                $channel,
                $command,
                $maxFrameSize,
            ))
            ->leftMap(static fn() => Failure::toPublish($command));
    }
}
