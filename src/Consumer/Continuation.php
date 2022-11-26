<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Consumer;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Type,
    Model\Basic\Ack,
    Model\Basic\Reject,
    Model\Basic\Cancel,
    Model\Basic\Recover,
    Failure,
    Client,
    Exception\BasicGetNotCancellable,
};
use Innmind\Immutable\{
    Either,
    Sequence,
};

/**
 * @template T
 */
final class Continuation
{
    /** @var T */
    private mixed $state;
    private State $response;

    /**
     * @param T $state
     */
    private function __construct(mixed $state, State $response)
    {
        $this->state = $state;
        $this->response = $response;
    }

    /**
     * @template A
     *
     * @param A $state
     *
     * @return self<A>
     */
    public static function of(mixed $state): self
    {
        // by default we auto ack the message
        return new self($state, State::ack);
    }

    /**
     * @param T $state
     *
     * @return self<T>
     */
    public function ack(mixed $state): self
    {
        return new self($state, State::ack);
    }

    /**
     * @param T $state
     *
     * @return self<T>
     */
    public function reject(mixed $state): self
    {
        return new self($state, State::reject);
    }

    /**
     * @param T $state
     *
     * @return self<T>
     */
    public function requeue(mixed $state): self
    {
        return new self($state, State::requeue);
    }

    /**
     * Only a consumer is cancellable, not a get
     *
     * @param T $state
     *
     * @return self<T>
     */
    public function cancel(mixed $state): self
    {
        return new self($state, State::cancel);
    }

    /**
     * @internal
     *
     * @param int<0, max> $deliveryTag
     *
     * @return Either<Failure, Client\State|Canceled>
     */
    public function respond(
        Connection $connection,
        Channel $channel,
        int $deliveryTag,
        string $consumerTag = null,
    ): Either {
        if ($this->response === State::cancel) {
            if (\is_null($consumerTag)) {
                // this means the user called self::cancel when inside a Get
                throw new BasicGetNotCancellable;
            }

            /** @var Either<Failure, Client\State|Canceled> */
            return $connection
                ->send(static fn($protocol) => $protocol->basic()->cancel(
                    $channel,
                    Cancel::of($consumerTag),
                ))
                ->match(
                    fn($connection) => $this->recover($connection, $channel),
                    fn($connection) => $this->recover($connection, $channel),
                    static fn() => Failure::toCancel,
                );
        }

        /** @var Either<Failure, Client\State|Canceled> */
        return $connection
            ->send(fn($protocol) => match ($this->response) {
                State::ack => $protocol->basic()->ack(
                    $channel,
                    Ack::of($deliveryTag),
                ),
                State::reject => $protocol->basic()->reject(
                    $channel,
                    Reject::of($deliveryTag),
                ),
                State::requeue => $protocol->basic()->reject(
                    $channel,
                    Reject::requeue($deliveryTag),
                ),
                State::cancel => Sequence::of(), // cancelling handled above
            })
            ->match(
                fn($connection) => Either::right(Client\State::of($connection, $this->state)),
                fn($connection) => Either::right(Client\State::of($connection, $this->state)),
                fn() => Either::left(match ($this->response) {
                    State::ack => Failure::toAck,
                    State::reject => Failure::toReject,
                    State::requeue => Failure::toReject,
                    State::cancel => Failure::toCancel,
                }),
            );
    }

    /**
     * @return Either<Failure, Canceled>
     */
    private function recover(
        Connection $connection,
        Channel $channel,
    ): Either {
        $read = new MessageReader;

        // walk over prefetched messages
        do {
            $frame = $connection->wait();

            if ($frame->type() === Type::method && $frame->is(Method::basicDeliver)) {
                // read all the frames for the prefetched message
                $read($connection);
            }
        } while (!$frame->is(Method::basicCancelOk));

        // requeue all the messages sent right before the cancel method
        //
        // by default prefetched messages won't be requeued until the channel
        // is closed thus the need to always call to recover otherwise a new call
        // to "consume" within the same channel will receive new messages but
        // won't receive previously prefetched messages leading to out of order
        // messages handling
        /** @var Either<Failure, Canceled> */
        return $connection
            ->send(static fn($protocol) => $protocol->basic()->recover(
                $channel,
                Recover::requeue(),
            ))
            ->wait(Method::basicRecoverOk)
            ->match(
                fn($connection) => Either::right(Canceled::of(Client\State::of($connection, $this->state))),
                fn($connection) => Either::right(Canceled::of(Client\State::of($connection, $this->state))),
                static fn() => Either::left(Failure::toRecover),
            );
    }
}
