<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client;

final class State
{
    private function __construct(private mixed $value)
    {
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
