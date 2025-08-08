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
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
    Predicate\Instance,
};

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
     * @psalm-pure
     *
     * @return Either<mixed, Value>
     */
    public static function wrap(mixed $value): Either
    {
        return Maybe::of($value)
            ->keep(Instance::of(PointInTime::class))
            ->either()
            ->map(static fn($point) => new self($point))
            ->leftMap(static fn(): mixed => $value);
    }

    /**
     * @psalm-pure
     *
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

    #[\Override]
    public function original(): PointInTime
    {
        return $this->original;
    }

    #[\Override]
    public function symbol(): Symbol
    {
        return Symbol::timestamp;
    }

    #[\Override]
    public function pack(): Str
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return UnsignedLongLongInteger::of(
            (int) $this->original->format(new TimestampFormat),
        )->pack();
    }
}
