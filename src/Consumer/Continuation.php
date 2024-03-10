<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Consumer;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame\Channel,
    Transport\Frame\Method,
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
    SideEffect,
};

final class Continuation
{
    private Client\State $state;
    private State $response;

    private function __construct(Client\State $state, State $response)
    {
        $this->state = $state;
        $this->response = $response;
    }

    public static function of(Client\State $state): self
    {
        // by default we auto ack the message
        return new self($state, State::ack);
    }

    public function ack(mixed $state): self
    {
        return new self(Client\State::of($state), State::ack);
    }

    public function reject(mixed $state): self
    {
        return new self(Client\State::of($state), State::reject);
    }

    public function requeue(mixed $state): self
    {
        return new self(Client\State::of($state), State::requeue);
    }

    /**
     * Only a consumer is cancellable, not a get
     *
     * Before canceling the consumer, the current message will be acknowledged
     */
    public function cancel(mixed $state): self
    {
        return new self(Client\State::of($state), State::cancel);
    }

    /**
     * @internal
     *
     * @param int<0, max> $deliveryTag
     *
     * @return Either<Failure, Client\State|Canceled>
     */
    public function respond(
        string $queue,
        Connection $connection,
        Channel $channel,
        MessageReader $read,
        int $deliveryTag,
        string $consumerTag = null,
    ): Either {
        /** @var Either<Failure, Client\State|Canceled> */
        return match ($this->response) {
            State::cancel => $this
                ->doAck($queue, $connection, $channel, $deliveryTag)
                ->flatMap(fn() => $this->doCancel(
                    $queue,
                    $connection,
                    $channel,
                    $consumerTag,
                ))
                ->flatMap(fn() => $this->recover($queue, $connection, $channel, $read)),
            State::ack => $this
                ->doAck($queue, $connection, $channel, $deliveryTag)
                ->map(fn() => $this->state),
            State::reject => $this
                ->doReject($queue, $connection, $channel, $deliveryTag)
                ->map(fn() => $this->state),
            State::requeue => $this
                ->doRequeue($queue, $connection, $channel, $deliveryTag)
                ->map(fn() => $this->state),
        };
    }

    /**
     * @return Either<Failure, Canceled>
     */
    private function recover(
        string $queue,
        Connection $connection,
        Channel $channel,
        MessageReader $read,
    ): Either {
        $received = $connection->wait();
        $walkOverPrefetchedMessages = $received->match(
            static fn($received) => $received->is(Method::basicDeliver),
            static fn() => false,
        );

        while ($walkOverPrefetchedMessages) {
            // read all the frames for the prefetched message then wait for next
            // frame
            $received = $received->flatMap(
                static fn() => $read($connection)->flatMap(
                    static fn() => $connection->wait(),
                ),
            );
            $walkOverPrefetchedMessages = $received->match(
                static fn($received) => $received->is(Method::basicDeliver),
                static fn() => false,
            );
        }

        // requeue all the messages sent right before the cancel method
        //
        // by default prefetched messages won't be requeued until the channel
        // is closed thus the need to always call to recover otherwise a new call
        // to "consume" within the same channel will receive new messages but
        // won't receive previously prefetched messages leading to out of order
        // messages handling
        /** @var Either<Failure, Canceled> */
        return $received
            ->flatMap(static fn() => $connection->request(
                static fn($protocol) => $protocol->basic()->recover(
                    $channel,
                    Recover::requeue(),
                ),
                Method::basicRecoverOk,
            ))
            ->map(fn() => Canceled::of($this->state))
            ->leftMap(static fn() => Failure::toRecover($queue));
    }

    /**
     * @param int<0, max> $deliveryTag
     *
     * @return Either<Failure, SideEffect>
     */
    private function doAck(
        string $queue,
        Connection $connection,
        Channel $channel,
        int $deliveryTag,
    ): Either {
        return $connection
            ->send(static fn($protocol) => $protocol->basic()->ack(
                $channel,
                Ack::of($deliveryTag),
            ))
            ->leftMap(static fn() => Failure::toAck($queue));
    }

    /**
     * @param int<0, max> $deliveryTag
     *
     * @return Either<Failure, SideEffect>
     */
    private function doReject(
        string $queue,
        Connection $connection,
        Channel $channel,
        int $deliveryTag,
    ): Either {
        return $connection
            ->send(static fn($protocol) => $protocol->basic()->reject(
                $channel,
                Reject::of($deliveryTag),
            ))
            ->leftMap(static fn() => Failure::toReject($queue));
    }

    /**
     * @param int<0, max> $deliveryTag
     *
     * @return Either<Failure, SideEffect>
     */
    private function doRequeue(
        string $queue,
        Connection $connection,
        Channel $channel,
        int $deliveryTag,
    ): Either {
        return $connection
            ->send(static fn($protocol) => $protocol->basic()->reject(
                $channel,
                Reject::requeue($deliveryTag),
            ))
            ->leftMap(static fn() => Failure::toReject($queue));
    }

    /**
     * @return Either<Failure, SideEffect>
     */
    private function doCancel(
        string $queue,
        Connection $connection,
        Channel $channel,
        ?string $consumerTag,
    ): Either {
        if (\is_null($consumerTag)) {
            // this means the user called self::cancel when inside a Get
            throw new BasicGetNotCancellable;
        }

        return $connection
            ->send(static fn($protocol) => $protocol->basic()->cancel(
                $channel,
                Cancel::of($consumerTag),
            ))
            ->leftMap(static fn() => Failure::toCancel($queue));
    }
}
