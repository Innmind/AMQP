<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Model\Basic\Message;

final class MessageFromAnotherConsumerReceived extends RuntimeException
{
    private Message $_message;
    private string $consumerTag;
    private int $deliveryTag;
    private bool $redelivered;
    private string $exchange;
    private string $routingKey;

    public function __construct(
        Message $message,
        string $consumerTag,
        int $deliveryTag,
        bool $redelivered,
        string $exchange,
        string $routingKey
    ) {
        $this->_message = $message;
        $this->consumerTag = $consumerTag;
        $this->deliveryTag = $deliveryTag;
        $this->redelivered = $redelivered;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
    }

    public function message(): Message
    {
        return $this->_message;
    }

    public function consumerTag(): string
    {
        return $this->consumerTag;
    }

    public function deliveryTag(): int
    {
        return $this->deliveryTag;
    }

    public function redelivered(): bool
    {
        return $this->redelivered;
    }

    public function exchange(): string
    {
        return $this->exchange;
    }

    public function routingKey(): string
    {
        return $this->routingKey;
    }
}
