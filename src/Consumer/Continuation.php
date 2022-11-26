<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Consumer;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Channel,
    Model\Basic\Ack,
    Model\Basic\Reject,
    Failure,
    Client,
};
use Innmind\Immutable\Either;

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
     * @internal
     *
     * @param int<0, max> $deliveryTag
     *
     * @return Either<Failure, Client\State>
     */
    public function respond(
        Connection $connection,
        Channel $channel,
        int $deliveryTag,
    ): Either {
        /** @var Either<Failure, Client\State> */
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
            })
            ->match(
                fn($connection) => Either::right(Client\State::of($connection, $this->state)),
                fn($connection) => Either::right(Client\State::of($connection, $this->state)),
                fn() => Either::left(match ($this->response) {
                    State::ack => Failure::toAck,
                    State::reject => Failure::toReject,
                    State::requeue => Failure::toReject,
                }),
            );
    }
}
