<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Exception\{
    DomainException,
    FrameExceedAllowedSize,
};

/**
 * @psalm-immutable
 */
final class MaxFrameSize
{
    /** @var int<0, 4294967295> */
    private int $value;

    /**
     * @param int<0, 4294967295> $value
     */
    private function __construct(int $value)
    {
        if ($value !== 0 && $value < 9) {
            throw new DomainException((string) $value);
        }

        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 4294967295> $value
     */
    public static function of(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     */
    public static function unlimited(): self
    {
        return new self(0);
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

    /**
     * @return int<0, 4294967295>
     */
    public function toInt(): int
    {
        return $this->value;
    }
}
