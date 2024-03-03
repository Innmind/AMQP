<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Command;

use Innmind\AMQP\{
    Command,
    Failure,
    Client\State,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Protocol,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Value,
    Transport\Frame\Method,
    Model\Basic\Consume as Model,
    Model\Basic\Message,
    Consumer\Details,
    Consumer\Continuation,
    Consumer\Canceled,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    Sequence,
    Predicate\Instance,
};

final class Consume implements Command
{
    private Model $command;
    /** @var callable(mixed, Message, Continuation, Details): Continuation */
    private $consume;

    /**
     * @param callable(mixed, Message, Continuation, Details): Continuation $consume
     */
    private function __construct(Model $command, callable $consume)
    {
        $this->command = $command;
        $this->consume = $consume;
    }

    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        mixed $state,
    ): Either {
        $frames = fn(Protocol $protocol): Sequence => $protocol->basic()->consume(
            $channel,
            $this->command,
        );

        $sideEffect = match ($this->command->shouldWait()) {
            true => $connection
                ->request($frames, Method::basicConsumeOk)
                ->flatMap(fn($frame) => $this->maybeStart(
                    $connection,
                    $channel,
                    $read,
                    $frame,
                    $state,
                )),
            false => $connection
                ->send($frames)
                ->map(static fn() => State::of($state)),
        };

        return $sideEffect->leftMap(
            fn() => Failure::toConsume($this->command),
        );
    }

    public static function of(string $queue): self
    {
        return new self(
            Model::of($queue),
            static fn(mixed $state, Message $_, Continuation $continuation) => $continuation->ack($state),
        );
    }

    /**
     * @param callable(mixed, Message, Continuation, Details): Continuation $consume
     */
    public function handle(callable $consume): self
    {
        return new self($this->command, $consume);
    }

    /**
     * @return Either<Failure, State>
     */
    private function maybeStart(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        Frame $frame,
        mixed $state,
    ): Either {
        return $frame
            ->values()
            ->first()
            ->keep(Instance::of(Value\ShortString::class))
            ->map(static fn($value) => $value->original()->toString())
            ->either()
            ->leftMap(fn() => Failure::toConsume($this->command))
            ->flatMap(fn($consumerTag) => $this->start(
                $connection,
                $channel,
                $read,
                $state,
                $consumerTag,
            ));
    }

    /**
     * @return Either<Failure, State>
     */
    private function start(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        mixed $state,
        string $consumerTag,
    ): Either {
        /** @var Either<Failure, State|Canceled> */
        $consumed = Either::right(State::of($state));
        // here the best approach would be to use recursion to avoid unwrapping
        // the monads but it would end up with a too deep call stack for inifite
        // consumers as each new message would mean a new function call in the
        // stack

        do {
            $consumed = $consumed
                ->map(static fn($state) => match (true) {
                    $state instanceof Canceled => $state->state(), // this SHOULD NEVER happen as we stop the loop below
                    default => $state,
                })
                ->flatMap(fn($state) => $this->waitDeliver(
                    $connection,
                    $channel,
                    $state->userState(),
                    $consumerTag,
                    $read,
                ));
        } while ($consumed->match(
            static fn($state) => !($state instanceof Canceled),
            static fn() => false,
        ));

        return $consumed->map(static fn($state) => match (true) {
            $state instanceof Canceled => $state->state(),
            default => $state,
        });
    }

    /**
     * @return Either<Failure, State|Canceled>
     */
    private function waitDeliver(
        Connection $connection,
        Channel $channel,
        mixed $state,
        string $consumerTag,
        MessageReader $read,
    ): Either {
        /** @var Either<Failure, State|Canceled> */
        return $connection
            ->wait(Method::basicDeliver)
            ->flatMap(
                fn($received) => $read($connection)->flatMap(
                    fn($message) => $this->maybeConsume(
                        $connection,
                        $channel,
                        $read,
                        $state,
                        $consumerTag,
                        $received->frame(),
                        $message,
                    ),
                ),
            )
            ->leftMap(fn() => Failure::toConsume($this->command));
    }

    /**
     * @return Either<Failure, State|Canceled>
     */
    private function maybeConsume(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        mixed $state,
        string $consumerTag,
        Frame $frame,
        Message $message,
    ): Either {
        $destinationConsumerTag = $frame
            ->values()
            ->first()
            ->keep(Instance::of(Value\ShortString::class))
            ->map(static fn($value) => $value->original()->toString())
            ->filter(static fn($destination) => $destination === $consumerTag);
        $deliveryTag = $frame
            ->values()
            ->get(1)
            ->keep(Instance::of(Value\UnsignedLongLongInteger::class))
            ->map(static fn($value) => $value->original());
        $redelivered = $frame
            ->values()
            ->get(2)
            ->keep(Instance::of(Value\Bits::class))
            ->flatMap(static fn($value) => $value->original()->first());
        $exchange = $frame
            ->values()
            ->get(3)
            ->keep(Instance::of(Value\ShortString::class))
            ->map(static fn($value) => $value->original()->toString());
        $routingKey = $frame
            ->values()
            ->get(4)
            ->keep(Instance::of(Value\ShortString::class))
            ->map(static fn($value) => $value->original()->toString());

        return Maybe::all($deliveryTag, $redelivered, $exchange, $routingKey)
            ->map(Details::ofConsume(...))
            ->flatMap(static fn($details) => $destinationConsumerTag->map(
                static fn() => $details, // this manipulation is to make sure the consumerTag is indeed for this consumer
            ))
            ->either()
            ->leftMap(fn() => Failure::toConsume($this->command))
            ->flatMap(fn($details) => $this->consume(
                $connection,
                $channel,
                $read,
                $state,
                $details,
                $message,
                $consumerTag,
            ));
    }

    /**
     * @return Either<Failure, State|Canceled>
     */
    private function consume(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        mixed $state,
        Details $details,
        Message $message,
        string $consumerTag,
    ): Either {
        return ($this->consume)(
            $state,
            $message,
            Continuation::of($state),
            $details,
        )
            ->respond(
                $this->command->queue(),
                $connection,
                $channel,
                $read,
                $details->deliveryTag(),
                $consumerTag,
            );
    }
}
