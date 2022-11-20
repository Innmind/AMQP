<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Consumer\Details,
    Consumer\Continuation,
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
 * @template T
 */
final class Get implements Command
{
    private Model $command;
    /** @var callable(T, Message, Continuation<T>, Details): Continuation<T> */
    private $consume;

    /**
     * @param callable(T, Message, Continuation<T>, Details): Continuation<T> $consume
     */
    private function __construct(Model $command, callable $consume)
    {
        $this->command = $command;
        $this->consume = $consume;
    }

    /**
     * @param T $state
     *
     * @psalm-suppress ImplementedReturnTypeMismatch TODO find why it complains
     * @return Either<Failure, array{Connection, T}>
     */
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
                fn($connection, $frame) => $this->maybeConsume(
                    $connection,
                    $channel,
                    $frame,
                    $state,
                ),
                static fn($connection) => Either::right([$connection, $state]), // this case should not happen
                static fn() => Either::left(Failure::toGet),
            );
    }

    /**
     * @return self<mixed>
     */
    public static function of(string $queue): self
    {
        /** @psalm-suppress MixedArgument */
        return new self(
            Model::of($queue),
            static fn(mixed $state, Message $_, Continuation $continuation) => $continuation->ack($state),
        );
    }

    /**
     * @template A
     *
     * @param callable(A, Message, Continuation<A>, Details): Continuation<A> $consume
     *
     * @return self<A>
     */
    public function handle(callable $consume): self
    {
        return new self($this->command, $consume);
    }

    /**
     * @param T $state
     *
     * @return Either<Failure, array{Connection, T}>
     */
    private function maybeConsume(
        Connection $connection,
        Channel $channel,
        Frame $frame,
        mixed $state,
    ): Either {
        if ($frame->is(Method::basicGetEmpty)) {
            /** @var Either<Failure, array{Connection, T}> */
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
            ->flatMap(fn($details) => $this->consume(
                $connection,
                $channel,
                $state,
                $message,
                $details,
            ));
    }

    /**
     * @param T $state
     *
     * @return Either<Failure, array{Connection, T}>
     */
    private function consume(
        Connection $connection,
        Channel $channel,
        mixed $state,
        Message $message,
        Details $details,
    ): Either {
        return ($this->consume)(
            $state,
            $message,
            Continuation::of($state),
            $details,
        )
            ->respond($connection, $channel, $details->deliveryTag());
    }
}
