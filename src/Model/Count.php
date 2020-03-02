<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model;

use Innmind\AMQP\Exception\DomainException;

final class Count
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
