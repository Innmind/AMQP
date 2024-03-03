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
        mixed $state,
    ): Either {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var Either<Failure, State>
         */
        return $this
            ->commands
            ->reduce(
                Either::right($connection),
                fn(Either $connection, $command) => $connection->flatMap(
                    fn(Connection $connection) => $this->publish($connection, $channel, $command),
                ),
            )
            ->map(static fn($connection) => State::of($connection, $state));
    }

    public static function one(Message $message): self
    {
        return new self(Sequence::of(Model::a($message)));
    }

    /**
     * @param Sequence<Message> $messages
     */
    public static function many(Sequence $messages): self
    {
        return new self($messages->map(Model::a(...)));
    }

    public function to(string $exchange): self
    {
        return new self($this->commands->map(
            static fn($publish) => $publish->to($exchange),
        ));
    }

    public function withRoutingKey(string $routingKey): self
    {
        return new self($this->commands->map(
            static fn($publish) => $publish->withRoutingKey($routingKey),
        ));
    }

    /**
     * @return Either<Failure, Connection>
     */
    private function publish(
        Connection $connection,
        Channel $channel,
        Model $command,
    ): Either {
        /** @var Either<Failure, Connection> */
        return $connection
            ->tell(static fn($protocol, $maxFrameSize) => $protocol->basic()->publish(
                $channel,
                $command,
                $maxFrameSize,
            ))
            ->map(static fn() => $connection)
            ->leftMap(static fn() => Failure::toPublish($command));
    }
}
