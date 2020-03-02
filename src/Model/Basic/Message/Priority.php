<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Exception\DomainException;

final class Priority
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 9) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
