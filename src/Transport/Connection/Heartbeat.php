<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Frame,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Period,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Heartbeat
{
    private Clock $clock;
    private Period $threshold;
    private PointInTime $lastReceivedData;

    private function __construct(
        Clock $clock,
        Period $threshold,
        PointInTime $lastReceivedData,
    ) {
        $this->clock = $clock;
        $this->threshold = $threshold;
        $this->lastReceivedData = $lastReceivedData;
    }

    public static function start(Clock $clock, Period $threshold): self
    {
        return new self($clock, $threshold, $clock->now());
    }

    /**
     * @return Sequence<Frame>
     */
    public function frames(): Sequence
    {
        if (
            $this
                ->clock
                ->now()
                ->elapsedSince($this->lastReceivedData)
                ->longerThan($this->threshold->asElapsedPeriod())
        ) {
            $this->lastReceivedData = $this->clock->now();

            return Sequence::of(Frame::heartbeat());
        }

        return Sequence::of();
    }

    public function active(): void
    {
        $this->lastReceivedData = $this->clock->now();
    }

    public function adjust(Period $threshold): self
    {
        return new self(
            $this->clock,
            $threshold,
            $this->lastReceivedData,
        );
    }
}
