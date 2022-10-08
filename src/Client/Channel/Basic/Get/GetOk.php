<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Basic\Get;

use Innmind\AMQP\{
    Client\Channel\Basic\Get,
    Model\Basic\Get as Command,
    Model\Basic\Message,
    Model\Basic\Ack,
    Model\Basic\Reject as RejectCommand,
    Transport\Connection,
    Transport\Frame\Channel,
    Exception\Reject,
    Exception\Requeue,
};

final class GetOk implements Get
{
    private Connection $connection;
    private Channel $channel;
    private Command $command;
    private Message $message;
    /** @var int<0, max> */
    private int $deliveryTag;
    private bool $redelivered;
    private string $exchange;
    private string $routingKey;
    private int $messageCount;
    private bool $consumed = false;

    /**
     * @param int<0, max> $deliveryTag
     */
    public function __construct(
        Connection $connection,
        Channel $channel,
        Command $command,
        Message $message,
        int $deliveryTag,
        bool $redelivered,
        string $exchange,
        string $routingKey,
        int $messageCount,
    ) {
        $this->connection = $connection;
        $this->channel = $channel;
        $this->command = $command;
        $this->message = $message;
        $this->deliveryTag = $deliveryTag;
        $this->redelivered = $redelivered;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->messageCount = $messageCount;
    }

    public function __invoke(callable $consume): void
    {
        if ($this->consumed) {
            return;
        }

        try {
            $consume(
                $this->message,
                $this->redelivered,
                $this->exchange,
                $this->routingKey,
                $this->messageCount,
            );

            if (!$this->command->shouldAutoAcknowledge()) {
                $this->connection->send($this->connection->protocol()->basic()->ack(
                    $this->channel,
                    new Ack($this->deliveryTag),
                ));
            }

            $this->consumed = true;
        } catch (Reject $e) {
            $this->reject();
        } catch (Requeue $e) {
            $this->requeue();
        } catch (\Throwable $e) {
            $this->reject();

            throw $e;
        }
    }

    private function reject(): void
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->reject(
                $this->channel,
                new RejectCommand($this->deliveryTag),
            ),
        );
    }

    private function requeue(): void
    {
        $this->connection->send(
            $this->connection->protocol()->basic()->reject(
                $this->channel,
                RejectCommand::requeue($this->deliveryTag),
            ),
        );
    }
}
