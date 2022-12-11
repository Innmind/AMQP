<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection,
    Transport\Frame,
    Failure,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    ElapsedPeriod,
};
use Innmind\Immutable\{
    Sequence,
    Either,
};

/**
 * @internal
 */
final class Heartbeat
{
    private Clock $clock;
    private ElapsedPeriod $threshold;
    private PointInTime $lastReceivedData;

    private function __construct(
        Clock $clock,
        ElapsedPeriod $threshold,
        PointInTime $lastReceivedData,
    ) {
        $this->clock = $clock;
        $this->threshold = $threshold;
        $this->lastReceivedData = $lastReceivedData;
    }

    public static function start(Clock $clock, ElapsedPeriod $threshold): self
    {
        return new self($clock, $threshold, $clock->now());
    }

    /**
     * @return Either<Failure, Connection>
     */
    public function ping(Connection $connection): Either
    {
        if (
            $this
                ->clock
                ->now()
                ->elapsedSince($this->lastReceivedData)
                ->longerThan($this->threshold)
        ) {
            return $connection
                ->send(static fn() => Sequence::of(Frame::heartbeat()))
                ->connection();
        }

        /** @var Either<Failure, Connection> */
        return Either::right($connection);
    }

    public function active(): self
    {
        return new self(
            $this->clock,
            $this->threshold,
            $this->clock->now(),
        );
    }

    public function adjust(ElapsedPeriod $threshold): self
    {
        $self = clone $this;
        $self->threshold = $threshold;

        return $self;
    }
}
