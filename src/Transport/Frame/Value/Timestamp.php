<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    TimeContinuum\Format\Timestamp as TimestampFormat,
};
use Innmind\TimeContinuum\{
    PointInTime as PointInTimeInterface,
    Earth\PointInTime\PointInTime,
    Earth\Format\ISO8601,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

/**
 * @implements Value<PointInTimeInterface>
 * @psalm-immutable
 */
final class Timestamp implements Value
{
    private PointInTimeInterface $original;

    private function __construct(PointInTimeInterface $point)
    {
        $this->original = $point;
    }

    /**
     * @psalm-pure
     */
    public static function of(PointInTimeInterface $point): self
    {
        return new self($point);
    }

    public static function unpack(Readable $stream): self
    {
        $time = UnsignedLongLongInteger::unpack($stream)->original();

        return new self(new PointInTime(
            \date((new ISO8601)->toString(), $time),
        ));
    }

    public function original(): PointInTimeInterface
    {
        return $this->original;
    }

    public function pack(): Str
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return UnsignedLongLongInteger::of(
            (int) $this->original->format(new TimestampFormat),
        )->pack();
    }
}
