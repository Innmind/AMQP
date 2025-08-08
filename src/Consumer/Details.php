<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Consumer;

use Innmind\AMQP\Model\Count;
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class Details
{
    /** @var int<0, max> */
    private int $deliveryTag;
    private bool $redelivered;
    private string $exchange;
    private string $routingKey;
    /** @var Maybe<Count> */
    private Maybe $messages;

    /**
     * @param int<0, max> $deliveryTag
     * @param Maybe<Count> $messages
     */
    private function __construct(
        int $deliveryTag,
        bool $redelivered,
        string $exchange,
        string $routingKey,
        Maybe $messages,
    ) {
        $this->deliveryTag = $deliveryTag;
        $this->redelivered = $redelivered;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->messages = $messages;
    }

    /**
     * @param int<0, max> $deliveryTag
     */
    #[\NoDiscard]
    public static function ofGet(
        int $deliveryTag,
        bool $redelivered,
        string $exchange,
        string $routingKey,
        Count $messages,
    ): self {
        return new self(
            $deliveryTag,
            $redelivered,
            $exchange,
            $routingKey,
            Maybe::just($messages),
        );
    }

    /**
     * @param int<0, max> $deliveryTag
     */
    #[\NoDiscard]
    public static function ofConsume(
        int $deliveryTag,
        bool $redelivered,
        string $exchange,
        string $routingKey,
    ): self {
        /** @var Maybe<Count> */
        $messages = Maybe::nothing();

        return new self(
            $deliveryTag,
            $redelivered,
            $exchange,
            $routingKey,
            $messages,
        );
    }

    /**
     * @internal
     *
     * @return int<0, max>
     */
    #[\NoDiscard]
    public function deliveryTag(): int
    {
        return $this->deliveryTag;
    }

    #[\NoDiscard]
    public function redelivered(): bool
    {
        return $this->redelivered;
    }

    #[\NoDiscard]
    public function exchange(): string
    {
        return $this->exchange;
    }

    #[\NoDiscard]
    public function routingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * Still left in the queue
     *
     * @return Maybe<Count>
     */
    #[\NoDiscard]
    public function messages(): Maybe
    {
        return $this->messages;
    }
}
