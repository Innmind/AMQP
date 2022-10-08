<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\TimeContinuum\ElapsedPeriod;

/**
 * @psalm-immutable
 */
final class TuneOk
{
    private MaxChannels $maxChannels;
    private MaxFrameSize $maxFrameSize;
    private ElapsedPeriod $heartbeat;

    public function __construct(
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        ElapsedPeriod $heartbeat,
    ) {
        $this->maxChannels = $maxChannels;
        $this->maxFrameSize = $maxFrameSize;
        $this->heartbeat = $heartbeat;
    }

    /**
     * @return int<0, 65535>
     */
    public function maxChannels(): int
    {
        return $this->maxChannels->toInt();
    }

    /**
     * @return int<0, 4294967295>
     */
    public function maxFrameSize(): int
    {
        return $this->maxFrameSize->toInt();
    }

    public function heartbeat(): ElapsedPeriod
    {
        return $this->heartbeat;
    }
}
