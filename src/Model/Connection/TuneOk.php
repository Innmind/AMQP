<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Exception\DomainException;
use Innmind\TimeContinuum\ElapsedPeriod;

final class TuneOk
{
    private $maxChannels;
    private $maxFrameSize;
    private $heartbeat;

    public function __construct(
        int $maxChannels,
        int $maxFrameSize,
        ElapsedPeriod $heartbeat
    ) {
        if (min($maxChannels, $maxFrameSize) < 0) {
            throw new DomainException;
        }

        $this->maxChannels = $maxChannels;
        $this->maxFrameSize = $maxFrameSize;
        $this->heartbeat = $heartbeat;
    }

    public function maxChannels(): int
    {
        return $this->maxChannels;
    }

    public function maxFrameSize(): int
    {
        return $this->maxFrameSize;
    }

    public function heartbeat(): ElapsedPeriod
    {
        return $this->heartbeat;
    }
}
