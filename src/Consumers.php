<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class Consumers
{
    private Map $consumers;

    public function __construct(Map $consumers = null)
    {
        $consumers ??= Map::of('string', 'callable');

        assertMap('string', 'callable', $consumers, 1);

        $this->consumers = $consumers;
    }

    public function contains(string $queue): bool
    {
        return $this->consumers->contains($queue);
    }

    public function get(string $queue): callable
    {
        return $this->consumers->get($queue);
    }
}
