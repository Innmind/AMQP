<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Exception\FrameChannelExceedAllowedChannelNumber;

/**
 * @psalm-immutable
 */
final class MaxChannels
{
    /** @var int<0, 65535> */
    private int $value;

    /**
     * @param int<0, 65535> $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 65535> $value
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

    public function allows(int $channel): bool
    {
        if ($this->value === 0) {
            return true;
        }

        return $channel <= $this->value;
    }

    /**
     * @throws FrameChannelExceedAllowedChannelNumber
     */
    public function verify(int $channel): void
    {
        if (!$this->allows($channel)) {
            throw new FrameChannelExceedAllowedChannelNumber($channel, $this);
        }
    }

    /**
     * @return int<0, 65535>
     */
    public function toInt(): int
    {
        return $this->value;
    }
}
