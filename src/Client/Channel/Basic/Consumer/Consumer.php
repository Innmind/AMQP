<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic\Consumer;

use Innmind\AMQP\{
    Client\Channel\Basic\Consumer as ConsumerInterface,
    Model\Basic\Consume,
    Model\Basic\Ack,
    Model\Basic\Reject as RejectCommand,
    Model\Basic\Cancel as CancelCommand,
    Model\Basic\Recover,
    Model\Basic\Message,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame\Channel,
    Transport\Frame\Type,
    Transport\Frame\Value,
    Transport\Frame\Method,
    Exception\MessageFromAnotherConsumerReceived,
    Exception\Requeue,
    Exception\Reject,
    Exception\Cancel,
};

final class Consumer implements ConsumerInterface
{
    private Connection $connection;
    private Consume $command;
    private Channel $channel;
    private string $consumerTag;
    private MessageReader $read;
    private ?int $take = null;
    /** @var \Closure(Message, bool, string, string): bool */
    private \Closure $predicate;
    private bool $canceled = false;

    public function __construct(
        Connection $connection,
        Consume $command,
        Channel $channel,
        string $consumerTag,
    ) {
        $this->connection = $connection;
        $this->command = $command;
        $this->channel = $channel;
        $this->consumerTag = $consumerTag;
        $this->read = new MessageReader;
        $this->predicate = static fn(): bool => true; // by default consume all messages
    }

    public function foreach(callable $consume): void
    {
        if ($this->canceled) {
            return;
        }

        while ($this->shouldConsume()) {
            $frame = $this->connection->wait(Method::basicDeliver);
            $message = ($this->read)($this->connection);
            /** @var Value\ShortString */
            $consumerTag = $frame->values()->first()->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException,
            );
            $consumerTag = $consumerTag->original()->toString();
            /** @var Value\UnsignedLongLongInteger */
            $deliveryTag = $frame->values()->get(1)->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException,
            );
            $deliveryTag = $deliveryTag->original();
            /** @var Value\Bits */
            $redelivered = $frame->values()->get(2)->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException,
            );
            $redelivered = $redelivered->original()->first()->match(
                static fn($bool) => $bool,
                static fn() => throw new \LogicException,
            );
            /** @var Value\ShortString */
            $exchange = $frame->values()->get(3)->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException,
            );
            $exchange = $exchange->original()->toString();
            /** @var Value\ShortString */
            $routingKey = $frame->values()->get(4)->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException,
            );
            $routingKey = $routingKey->original()->toString();

            try {
                if ($this->consumerTag !== $consumerTag) {
                    throw new MessageFromAnotherConsumerReceived(
                        $message,
                        $consumerTag,
                        $deliveryTag,
                        $redelivered,
                        $exchange,
                        $routingKey,
                    );
                }

                $toProcess = ($this->predicate)(
                    $message,
                    $redelivered,
                    $exchange,
                    $routingKey,
                );

                if (!$toProcess) {
                    throw new Requeue;
                }

                // flag before the consume call as the "take" applies to the number
                // of messages that match the predicate and the number of
                // successfully consumed messages
                $this->flagConsumed();

                $consume(
                    $message,
                    $redelivered,
                    $exchange,
                    $routingKey,
                );

                $this->ack($deliveryTag);
            } catch (Reject $e) {
                $this->reject($deliveryTag);
            } catch (Requeue $e) {
                $this->requeue($deliveryTag);
            } catch (Cancel $e) {
                $this->ack($deliveryTag);

                break;
            } catch (\Throwable $e) {
                $this->requeue($deliveryTag);
                $this->cancel();

                throw $e;
            }
        }

        $this->cancel();
    }

    public function take(int $count): void
    {
        $this->take = $count;
    }

    public function filter(callable $predicate): void
    {
        $this->predicate = \Closure::fromCallable($predicate);
    }

    private function shouldConsume(): bool
    {
        if ($this->canceled()) {
            return false;
        }

        if (\is_null($this->take)) {
            return true;
        }

        return $this->take > 0;
    }

    private function flagConsumed(): void
    {
        if (\is_null($this->take)) {
            return;
        }

        --$this->take;
    }

    /**
     * @param int<0, max> $deliveryTag
     */
    private function ack(int $deliveryTag): void
    {
        if ($this->command->shouldAutoAcknowledge()) {
            return;
        }

        $_ = $this
            ->connection
            ->send(
                $this->connection->protocol()->basic()->ack(
                    $this->channel,
                    Ack::of($deliveryTag),
                ),
            )
            ->match(
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }

    /**
     * @param int<0, max> $deliveryTag
     */
    private function reject(int $deliveryTag): void
    {
        $_ = $this
            ->connection
            ->send(
                $this->connection->protocol()->basic()->reject(
                    $this->channel,
                    RejectCommand::of($deliveryTag),
                ),
            )
            ->match(
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }

    /**
     * @param int<0, max> $deliveryTag
     */
    private function requeue(int $deliveryTag): void
    {
        $_ = $this
            ->connection
            ->send(
                $this->connection->protocol()->basic()->reject(
                    $this->channel,
                    RejectCommand::requeue($deliveryTag),
                ),
            )
            ->match(
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
    }

    private function canceled(): bool
    {
        if ($this->canceled) {
            return true;
        }

        return $this->connection->closed();
    }

    private function cancel(): void
    {
        if ($this->canceled()) {
            return;
        }

        $_ = $this
            ->connection
            ->send($this->connection->protocol()->basic()->cancel(
                $this->channel,
                CancelCommand::of($this->consumerTag),
            ))
            ->match(
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );

        // walk over prefetched messages
        do {
            $frame = $this->connection->wait();

            if ($frame->type() === Type::method && $frame->is(Method::basicDeliver)) {
                // read all the frames for the prefetched message
                ($this->read)($this->connection);
            }
        } while (!$frame->is(Method::basicCancelOk));

        // requeue all the messages sent right before the cancel method
        //
        // by default prefetched messages won't be requeued until the channel
        // is closed thus the need to always call to recover otherwise a new call
        // to "consume" within the same channel will receive new messages but
        // won't receive previously prefetched messages leading to out of order
        // messages handling
        $_ = $this
            ->connection
            ->send($this->connection->protocol()->basic()->recover(
                $this->channel,
                Recover::requeue(),
            ))
            ->map(static fn($connection) => $connection->wait(Method::basicRecoverOk))
            ->match(
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );

        $this->canceled = true;
    }
}
