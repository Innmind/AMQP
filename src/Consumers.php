<?php
declare(strict_types = 1);

namespace Innmind\AMQP;

use Innmind\Immutable\{
    MapInterface,
    Map,
};

final class Consumers
{
    private $consumers;

    public function __construct(MapInterface $consumers = null)
    {
        $consumers = $consumers ?? new Map('string', 'callable');

        if (
            (string) $consumers->keyType() !== 'string' ||
            (string) $consumers->valueType() !== 'callable'
        ) {
            throw new \TypeError('Argument 1 must be of type MapInterface<string, callable>');
        }

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
