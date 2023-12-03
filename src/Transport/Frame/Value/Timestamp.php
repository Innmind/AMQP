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
use Innmind\IO\Readable\Stream;
use Innmind\Socket\Client;
use Innmind\Immutable\{
    Str,
    Maybe,
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
     * @param Stream<Client> $stream
     *
     * @return Maybe<self>
     */
    public static function unpack(Clock $clock, Stream $stream): Maybe
    {
        return UnsignedLongLongInteger::unpack($stream)
            ->map(static fn($time) => $time->original())
            ->flatMap(static fn($time) => $clock->at((string) $time, new TimestampFormat))
            ->map(static fn($point) => new self($point));
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
