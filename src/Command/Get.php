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
    Sequence,
    Predicate\Instance,
};

final class Get implements Command
{
    private Model $command;
    /** @var callable(mixed, Message, Continuation<mixed>, Details): Continuation<mixed> */
    private $consume;
    /** @var positive-int */
    private int $take;

    /**
     * @param callable(mixed, Message, Continuation<mixed>, Details): Continuation<mixed> $consume
     * @param positive-int $take
     */
    private function __construct(Model $command, callable $consume, int $take)
    {
        $this->command = $command;
        $this->consume = $consume;
        $this->take = $take;
    }

    /**
     * @return Either<Failure, array{Connection, mixed}>
     */
    public function __invoke(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either {
        /**
         * @psalm-suppress MixedArrayAccess
         * @psalm-suppress MixedArgument
         * @var Either<Failure, array{Connection, mixed}>
         */
        return Sequence::of(...\array_fill(0, $this->take, null))->reduce(
            Either::right([$connection, $state]),
            fn(Either $pair) => $pair->flatMap(
                fn($pair) => $this->doGet($pair[0], $channel, $pair[1]),
            ),
        );
    }

    public static function of(string $queue): self
    {
        return new self(
            Model::of($queue),
            static fn(mixed $state, Message $_, Continuation $continuation) => $continuation->ack($state),
            1,
        );
    }

    /**
     * @template A
     *
     * @param callable(A, Message, Continuation<A>, Details): Continuation<A> $consume
     */
    public function handle(callable $consume): self
    {
        return new self($this->command, $consume, $this->take);
    }

    /**
     * @param positive-int $take
     */
    public function take(int $take): self
    {
        return new self($this->command, $this->consume, $take);
    }

    /**
     * @return Either<Failure, array{Connection, mixed}>
     */
    public function doGet(
        Connection $connection,
        Channel $channel,
        mixed $state,
    ): Either {
        /** @var Either<Failure, array{Connection, mixed}> */
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
     * @return Either<Failure, array{Connection, mixed}>
     */
    private function maybeConsume(
        Connection $connection,
        Channel $channel,
        Frame $frame,
        mixed $state,
    ): Either {
        if ($frame->is(Method::basicGetEmpty)) {
            /** @var Either<Failure, array{Connection, mixed}> */
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

        /** @var Either<Failure, array{Connection, mixed}> */
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
     * @return Either<Failure, array{Connection, mixed}>
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
