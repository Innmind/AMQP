<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\TimeContinuum\ElapsedPeriod;

final class TuneOk
{
    private $maxChannels;
    private $maxFrameSize;
    private $heartbeat;

    public function __construct(
        MaxChannels $maxChannels,
        MaxFrameSize $maxFrameSize,
        ElapsedPeriod $heartbeat
    ) {
        $this->maxChannels = $maxChannels;
        $this->maxFrameSize = $maxFrameSize;
        $this->heartbeat = $heartbeat;
    }

    public function maxChannels(): int
    {
        return $this->maxChannels->toInt();
    }

    public function maxFrameSize(): int
    {
        return $this->maxFrameSize->toInt();
    }

    public function heartbeat(): ElapsedPeriod
    {
        return $this->heartbeat;
    }
}
