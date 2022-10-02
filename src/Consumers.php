<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Model\Basic\Message;
use Innmind\Immutable\Map;

final class Consumers
{
    /** @var Map<string, callable(Message, bool, string, string): void> */
    private Map $consumers;

    /**
     * @param Map<string, callable(Message, bool, string, string): void> $consumers
     */
    public function __construct(Map $consumers)
    {
        $this->consumers = $consumers;
    }

    public function contains(string $queue): bool
    {
        return $this->consumers->contains($queue);
    }

    /**
     * @return callable(Message, bool, string, string): void
     */
    public function get(string $queue): callable
    {
        return $this->consumers->get($queue)->match(
            static fn($consumer) => $consumer,
            static fn() => throw new \LogicException,
        );
    }
}
