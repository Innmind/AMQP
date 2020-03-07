<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

final class Type
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
