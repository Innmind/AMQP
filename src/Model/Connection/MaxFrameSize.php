<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Exception\{
    DomainException,
    FrameExceedAllowedSize,
};

final class MaxFrameSize
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value !== 0 && $value < 9) {
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

    /**
     * @throws FrameExceedAllowedSize
     */
    public function verify(int $size): void
    {
        if (!$this->allows($size)) {
            throw new FrameExceedAllowedSize($size, $this);
        }
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
