<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model;

use Innmind\AMQP\Exception\DomainException;

/**
 * @psalm-immutable
 */
final class Count
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new DomainException((string) $value);
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
