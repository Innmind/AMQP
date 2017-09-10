<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic\Consumer;

use Innmind\AMQP\{
    Client\Channel\Basic\Consumer as ConsumerInterface,
    Model\Basic\Consume,
    Model\Basic\Ack,
    Model\Basic\Reject as RejectCommand,
    Model\Basic\Cancel as CancelCommand,
    Model\Basic\Message\Locked,
    Transport\Connection,
    Transport\Connection\MessageReader,
    Transport\Frame\Channel,
    Transport\Frame\Type,
    Exception\MessageFromAnotherConsumerReceived,
    Exception\Requeue,
    Exception\Reject,
    Exception\Cancel
};

final class Consumer implements ConsumerInterface
{
    private $connection;
    private $command;
    private $channel;
    private $consumerTag;
    private $read;
    private $take;
    private $predicate;
    private $canceled = false;

    public function __construct(
        Connection $connection,
        Consume $command,
        Channel $channel,
        string $consumerTag
    ) {
        $this->connection = $connection;
        $this->command = $command;
        $this->channel = $channel;
        $this->consumerTag = $consumerTag;
        $this->read = new MessageReader;
        $this->predicate = static function(): bool {
            return true; //by default consume all messages
        };
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $consume): void
    {
        if ($this->canceled) {
            return;
        }

        while ($this->shouldConsume()) {
            try {
                $frame = $this->connection->wait('basic.deliver');
                $message = ($this->read)($this->connection);
                $message = new Locked($message);
                $consumerTag = (string) $frame->values()->first()->original();
                $deliveryTag = $frame->values()->get(1)->original()->value();
                $redelivered = $frame->values()->get(2)->original()->first();
                $exchange = (string) $frame->values()->get(3)->original();
                $routingKey = (string) $frame->values()->get(4)->original();

                if ($this->consumerTag !== $consumerTag) {
                    throw new MessageFromAnotherConsumerReceived(
                        $message,
                        $consumerTag,
                        $deliveryTag,
                        $redelivered,
                        $exchange,
                        $routingKey
                    );
                }

                $toProcess = ($this->predicate)(
                    $message,
                    $redelivered,
                    $exchange,
                    $routingKey
                );

                if (!$toProcess) {
                    throw new Requeue;
                }

                //flag before the consume call as the "take" applies to the number
                //of messages that match the predicate and the number of
                //successfully consumed messages
                $this->flagConsumed();

                $consume(
                    $message,
                    $redelivered,
                    $exchange,
                    $routingKey
                );

                if (!$this->command->shouldAutoAcknowledge()) {
                    $this->connection->send(
                        $this->connection->protocol()->basic()->ack(
                            $this->channel,
                            new Ack($deliveryTag)
                        )
                    );
                }
            } catch (Reject $e) {
                $this->reject($deliveryTag);
            } catch (Requeue $e) {
                $this->requeue($deliveryTag);
            } catch (Cancel $e) {
                break;
            } catch (\Throwable $e) {
                $this->cancel();

                throw $e;
            }
        }

        $this->cancel();
    }

    /**
     * {@inheritdoc}
     */
    public function take(int $count): ConsumerInterface
    {
        $this->take = $count;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): ConsumerInterface
    {
        $this->predicate = $predicate;

        return $this;
    }

    private function shouldConsume(): bool
    {
        if ($this->canceled()) {
            return false;
        }

        if (is_null($this->take)) {
            return true;
        }

        return $this->take > 0;
    }

    private function flagConsumed(): void
    {
        if (is_null($this->take)) {
            return;
        }

        --$this->take;
    }

    private function reject(int $deliveryTag): void
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->reject(
                $this->channel,
                new RejectCommand($deliveryTag)
            )
        );
    }

    private function requeue(int $deliveryTag): void
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->reject(
                $this->channel,
                RejectCommand::requeue($deliveryTag)
            )
        );
    }

    private function canceled(): bool
    {
        if ($this->canceled) {
            return true;
        }

        return !$this->connection->opened();
    }

    private function cancel(): void
    {
        if ($this->canceled()) {
            return;
        }

        $this->connection->send($this->connection->protocol()->basic()->cancel(
            $this->channel,
            new CancelCommand($this->consumerTag)
        ));

        $deliver = $this->connection->protocol()->method('basic.deliver');
        $expected = $this->connection->protocol()->method('basic.cancel-ok');

        do {
            $frame = $this->connection->wait();

            if (
                $frame->type() === Type::method() &&
                $frame->method()->equals($deliver)
            ) {
                $message = ($this->read)($this->connection);
                $this->requeue($frame->values()->get(1)->original()->value());
            }
        } while (!$frame->method()->equals($expected));

        $this->canceled = true;
    }
}
