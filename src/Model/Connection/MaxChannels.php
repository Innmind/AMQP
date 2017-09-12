<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Exception\DomainException;

final class MaxChannels
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function allows(int $channel): bool
    {
        if ($this->value === 0) {
            return true;
        }

        return $channel <= $this->value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
