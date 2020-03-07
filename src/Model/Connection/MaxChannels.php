<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Exception\{
    DomainException,
    FrameChannelExceedAllowedChannelNumber,
};

final class MaxChannels
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new DomainException((string) $value);
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

    /**
     * @throws FrameChannelExceedAllowedChannelNumber
     */
    public function verify(int $channel): void
    {
        if (!$this->allows($channel)) {
            throw new FrameChannelExceedAllowedChannelNumber($channel, $this);
        }
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
