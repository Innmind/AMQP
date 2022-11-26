<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Consumer;

use Innmind\AMQP\Client\State;

final class Canceled
{
    private State $state;

    private function __construct(State $state)
    {
        $this->state = $state;
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
