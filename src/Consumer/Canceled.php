<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Consumer;

use Innmind\AMQP\Client\State;

/**
 * @internal
 */
final class Canceled
{
    private function __construct(private State $state)
    {
    }

    public static function of(State $state): self
    {
        return new self($state);
    }

    public function state(): State
    {
        return $this->state;
    }
}
