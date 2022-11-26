<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Consumer;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame\Channel,
    Model\Basic\Ack,
    Model\Basic\Reject,
    Failure,
};
use Innmind\Immutable\Either;

final class Continuation
{
    private mixed $state;
    private State $response;

    private function __construct(mixed $state, State $response)
    {
        $this->state = $state;
        $this->response = $response;
    }

    public static function of(mixed $state): self
    {
        // by default we auto ack the message
        return new self($state, State::ack);
    }

    public function ack(mixed $state): self
    {
        return new self($state, State::ack);
    }

    public function reject(mixed $state): self
    {
        return new self($state, State::reject);
    }

    public function requeue(mixed $state): self
    {
        return new self($state, State::requeue);
    }

    /**
     * @internal
     *
     * @param int<0, max> $deliveryTag
     *
     * @return Either<Failure, array{Connection, mixed}>
     */
    public function respond(
        Connection $connection,
        Channel $channel,
        int $deliveryTag,
    ): Either {
        /** @var Either<Failure, array{Connection, mixed}> */
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
                fn($connection) => Either::right([$connection, $this->state]),
                fn($connection) => Either::right([$connection, $this->state]),
                fn() => Either::left(match ($this->response) {
                    State::ack => Failure::toAck,
                    State::reject => Failure::toReject,
                    State::requeue => Failure::toReject,
                }),
            );
    }
}
