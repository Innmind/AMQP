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
    Attempt,
    Sequence,
    Predicate\Instance,
};

final class Get implements Command
{
    private Model $command;
    /** @var callable(mixed, Message, Continuation, Details): Continuation */
    private $consume;
    /** @var positive-int */
    private int $take;

    /**
     * @param callable(mixed, Message, Continuation, Details): Continuation $consume
     * @param positive-int $take
     */
    private function __construct(Model $command, callable $consume, int $take)
    {
        $this->command = $command;
        $this->consume = $consume;
        $this->take = $take;
    }

    #[\Override]
    public function __invoke(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
    ): Attempt {
        return Sequence::of(...\array_fill(0, $this->take, null))
            ->sink($state)
            ->attempt(fn($state) => $this->doGet(
                $connection,
                $channel,
                $read,
                $state,
            ));
    }

    #[\NoDiscard]
    public static function of(string $queue): self
    {
        return new self(
            Model::of($queue),
            static fn(mixed $state, Message $_, Continuation $continuation) => $continuation->ack($state),
            1,
        );
    }

    /**
     * @param callable(mixed, Message, Continuation, Details): Continuation $consume
     */
    #[\NoDiscard]
    public function handle(callable $consume): self
    {
        return new self($this->command, $consume, $this->take);
    }

    /**
     * @param positive-int $take
     */
    #[\NoDiscard]
    public function take(int $take): self
    {
        return new self($this->command, $this->consume, $take);
    }

    /**
     * @return Attempt<State>
     */
    public function doGet(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
    ): Attempt {
        return $connection
            ->request(
                fn($protocol) => $protocol->basic()->get(
                    $channel,
                    $this->command,
                ),
                Method::basicGetOk,
                Method::basicGetEmpty,
            )
            ->flatMap(
                fn($frame) => $this->maybeConsume(
                    $connection,
                    $channel,
                    $read,
                    $frame,
                    $state,
                ),
            )
            ->mapError(Failure::as(Failure::toGet($this->command)));
    }

    /**
     * @return Attempt<State>
     */
    private function maybeConsume(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        Frame $frame,
        State $state,
    ): Attempt {
        if ($frame->is(Method::basicGetEmpty)) {
            return Attempt::result($state);
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

        return Maybe::all($deliveryTag, $redelivered, $exchange, $routingKey, $messageCount)
            ->map(Details::ofGet(...))
            ->attempt(fn() => Failure::toGet($this->command))
            ->flatMap(
                fn($details) => $read($connection)->flatMap(
                    fn($message) => $this->consume(
                        $connection,
                        $channel,
                        $read,
                        $state,
                        $message,
                        $details,
                    ),
                ),
            );
    }

    /**
     * @return Attempt<State>
     */
    private function consume(
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        State $state,
        Message $message,
        Details $details,
    ): Attempt {
        return ($this->consume)(
            $state->unwrap(),
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
            )
            ->map(static fn($state) => match (true) {
                $state instanceof Canceled => $state->state(),
                default => $state,
            })
            ->attempt(static fn($failure) => $failure);
    }
}
