<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Client\State,
    Consumer\Details,
    Consumer\Continuation,
    Consumer\Canceled,
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
        return Sequence::of(...\array_fill(0, $this->take, null))->reduce(
            Either::right(State::of($connection, $state)),
            fn(Either $state) => $state->flatMap(
                fn(State $state) => $this->doGet(
                    $state->connection(),
                    $channel,
                    $read,
                    $state->userState(),
                ),
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
     * @return Either<Failure, State>
     */
    public function doGet(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        mixed $state,
    ): Either {
        /** @var Either<Failure, State> */
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
                    $read,
                    $frame,
                    $state,
                ),
                static fn($connection) => Either::right(State::of($connection, $state)), // this case should not happen
                static fn() => Either::left(Failure::toGet),
            );
    }

    /**
     * @return Either<Failure, State>
     */
    private function maybeConsume(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        Frame $frame,
        mixed $state,
    ): Either {
        if ($frame->is(Method::basicGetEmpty)) {
            /** @var Either<Failure, State> */
            return Either::right(State::of($connection, $state));
        }

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

        /** @var Either<Failure, State> */
        return Maybe::all($deliveryTag, $redelivered, $exchange, $routingKey, $messageCount)
            ->map(Details::ofGet(...))
            ->either()
            ->leftMap(static fn() => Failure::toGet)
            ->flatMap(
                fn($details) => $read($connection)->flatMap(
                    fn($received) => $this->consume(
                        $received->connection(),
                        $channel,
                        $read,
                        $state,
                        $received->message(),
                        $details,
                    ),
                ),
            );
    }

    /**
     * @return Either<Failure, State>
     */
    private function consume(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
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
            ->respond($connection, $channel, $read, $details->deliveryTag())
            ->map(static fn($state) => match ($state instanceof Canceled) {
                true => $state->state(),
                false => $state,
            });
    }
}
