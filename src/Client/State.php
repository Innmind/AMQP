<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

final class State
{
    private mixed $value;

    private function __construct(mixed $value)
    {
        $this->value = $value;
    }

    public static function of(mixed $value): self
    {
        return new self($value);
    }

    public function unwrap(): mixed
    {
        return $this->value;
    }
}
