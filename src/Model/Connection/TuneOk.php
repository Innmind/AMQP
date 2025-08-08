<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\TimeContinuum\Period;

/**
 * @psalm-immutable
 */
final class TuneOk
{
    private function __construct(
        private MaxChannels $maxChannels,
        private MaxFrameSize $maxFrameSize,
        private Period $heartbeat,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        Period $heartbeat,
    ): self {
        return new self($maxChannels, $maxFrameSize, $heartbeat);
    }

    /**
     * @return int<0, 65535>
     */
    #[\NoDiscard]
    public function maxChannels(): int
    {
        return $this->maxChannels->toInt();
    }

    /**
     * @return int<0, 4294967295>
     */
    #[\NoDiscard]
    public function maxFrameSize(): int
    {
        return $this->maxFrameSize->toInt();
    }

    #[\NoDiscard]
    public function heartbeat(): Period
    {
        return $this->heartbeat;
    }
}
