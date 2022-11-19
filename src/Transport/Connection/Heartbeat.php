<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\Transport\{
    Connection,
    Frame,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    ElapsedPeriod,
};
use Innmind\Immutable\Sequence;

final class Heartbeat
{
    private Clock $clock;
    private ElapsedPeriod $threshold;
    private PointInTime $lastReceivedData;

    public function __construct(Clock $clock, ElapsedPeriod $threshold)
    {
        $this->clock = $clock;
        $this->threshold = $threshold;
        $this->lastReceivedData = $clock->now();
    }

    public function ping(Connection $connection): void
    {
        if (
            $this
                ->clock
                ->now()
                ->elapsedSince($this->lastReceivedData)
                ->longerThan($this->threshold)
        ) {
            $_ = $connection
                ->send(static fn() => Sequence::of(Frame::heartbeat()))
                ->match(
                    static fn() => null,
                    static fn() => null,
                    static fn() => throw new \RuntimeException,
                );
        }
    }

    public function active(): void
    {
        $this->lastReceivedData = $this->clock->now();
    }

    public function adjust(ElapsedPeriod $threshold): self
    {
        $self = clone $this;
        $self->threshold = $threshold;

        return $self;
    }
}
