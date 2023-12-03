<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    TimeContinuum\Format\Timestamp as TimestampFormat,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
};
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Str;

/**
 * @implements Value<PointInTime>
 * @psalm-immutable
 */
final class Timestamp implements Value
{
    private PointInTime $original;

    private function __construct(PointInTime $point)
    {
        $this->original = $point;
    }

    /**
     * @psalm-pure
     */
    public static function of(PointInTime $point): self
    {
        return new self($point);
    }

    /**
     * @return Frame<Unpacked<self>>
     */
    public static function frame(Clock $clock): Frame
    {
        return UnsignedLongLongInteger::frame()->flatMap(
            static fn($time) => $clock
                ->at((string) $time->unwrap()->original(), new TimestampFormat)
                ->map(static fn($point) => new self($point))
                ->map(static fn($value) => Unpacked::of(
                    $time->read(),
                    $value,
                ))
                ->match(
                    static fn($unpacked) => Frame\NoOp::of($unpacked),
                    static fn() => Frame\NoOp::of(Unpacked::of(
                        0,
                        new self($clock->now()),
                    ))->filter(static fn() => false), // to force failing since the read time is invalid
                ),
        );
    }

    public function original(): PointInTime
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::timestamp;
    }

    public function pack(): Str
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return UnsignedLongLongInteger::of(
            (int) $this->original->format(new TimestampFormat),
        )->pack();
    }
}
