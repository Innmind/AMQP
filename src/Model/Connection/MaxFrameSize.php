<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Exception\DomainException;

final class MaxFrameSize
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0 || ($value !== 0 && $value < 9)) {
            throw new DomainException((string) $value);
        }

        $this->value = $value;
    }

    public function isLimited(): bool
    {
        return $this->value > 0;
    }

    public function allows(int $size): bool
    {
        if (!$this->isLimited()) {
            return true;
        }

        return $size <= $this->value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
