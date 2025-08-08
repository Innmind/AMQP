<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Exception\FrameChannelExceedAllowedChannelNumber;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @psalm-immutable
 */
final class MaxChannels
{
    /**
     * @param int<0, 65535> $value
     */
    private function __construct(private int $value)
    {
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 65535> $value
     */
    #[\NoDiscard]
    public static function of(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function unlimited(): self
    {
        return new self(0);
    }

    #[\NoDiscard]
    public function allows(int $channel): bool
    {
        if ($this->value === 0) {
            return true;
        }

        return $channel <= $this->value;
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function verify(int $channel): Attempt
    {
        if (!$this->allows($channel)) {
            return Attempt::error(new FrameChannelExceedAllowedChannelNumber($channel, $this));
        }

        return Attempt::result(SideEffect::identity());
    }

    /**
     * @return int<0, 65535>
     */
    #[\NoDiscard]
    public function toInt(): int
    {
        return $this->value;
    }
}
