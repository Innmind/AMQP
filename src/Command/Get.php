<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Consumer\Details,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Model\Basic\Get as Model,
    Model\Basic\Message,
    Model\Count,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    Predicate\Instance,
};

/**
 * @template S
 * @template T
 * @implements Command<S, T>
 */
final class Get implements Command
{
    private Model $command;
    /** @var callable(S, Message, Details): T */
    private $consume;

    /**
     * @param callable(S, Message, Details): T $consume
     */
    private function __construct(Model $command, callable $consume)
    {
        $this->command = $command;
        $this->consume = $consume;
    }

    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either {
        /** @var Either<Failure, array{Connection, T}> */
        return $connection
            ->send(fn($protocol) => $protocol->basic()->get(
                $channel,
                $this->command,
            ))
            ->wait(Method::basicGetOk, Method::basicGetEmpty)
            ->match(
                fn($connection, $frame) => $this->maybeConsume($connection, $frame, $state),
                static fn($connection) => Either::right([$connection, $state]), // this case should not happen
                static fn() => Either::left(Failure::toGet),
            );
    }

    /**
     * @template A
     *
     * @return self<A, A>
     */
    public static function of(string $queue): self
    {
        return new self(
            Model::of($queue)->autoAcknowledge(),
            static fn(mixed $state): mixed => $state,
        );
    }

    /**
     * @template A
     *
     * @param callable(S, Message, Details): A $consume
     *
     * @return self<S, A>
     */
    public function handle(callable $consume): self
    {
        return new self($this->command, $consume);
    }

    /**
     * @param S $state
     */
    private function maybeConsume(
        Connection $connection,
        Frame $frame,
        mixed $state,
    ): Either {
        if ($frame->is(Method::basicGetEmpty)) {
            return Either::right([$connection, $state]);
        }

        $message = (new MessageReader)($connection);
        $deliveryTag = $frame
            ->values()
            ->first()
            ->keep(Instance::of(Value\UnsignedLongLongInteger::class))
            ->map(static fn($value) => $value->original());
        $redelivered = $frame
            ->values()
            ->get(1)
            ->keep(Instance::of(Value\Bits::class))
            ->flatMap(static fn($value) => $value->original()->first());
        $exchange = $frame
            ->values()
            ->get(2)
            ->keep(Instance::of(Value\ShortString::class))
            ->map(static fn($value) => $value->original()->toString());
        $routingKey = $frame
            ->values()
            ->get(3)
            ->keep(Instance::of(Value\ShortString::class))
            ->map(static fn($value) => $value->original()->toString());
        $messageCount = $frame
            ->values()
            ->get(4)
            ->keep(Instance::of(Value\UnsignedLongInteger::class))
            ->map(static fn($value) => $value->original())
            ->map(Count::of(...));

        /** @var Either<Failure, array{Connection, T}> */
        return Maybe::all($deliveryTag, $redelivered, $exchange, $routingKey, $messageCount)
            ->map(Details::ofGet(...))
            ->either()
            ->leftMap(static fn() => Failure::toGet)
            ->map(fn($details) => $this->consume(
                $state,
                $message,
                $details,
            ))
            ->map(static fn($state) => [$connection, $state]);
    }

    /**
     * @param S $state
     *
     * @return T
     */
    private function consume(
        mixed $state,
        Message $message,
        Details $details,
    ): mixed {
        return ($this->consume)($state, $message, $details);
    }
}
