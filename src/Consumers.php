<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\AMQP\Model\Basic\Message;
use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class Consumers
{
    /** @var Map<string, callable(Message, bool, string, string): void> */
    private Map $consumers;

    /**
     * @param Map<string, callable(Message, bool, string, string): void>|null $consumers
     */
    public function __construct(Map $consumers = null)
    {
        /** @var Map<string, callable(Message, bool, string, string): void> */
        $consumers ??= Map::of('string', 'callable');

        assertMap('string', 'callable', $consumers, 1);

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
        return $this->consumers->get($queue);
    }
}
