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
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;

/**
 * @implements Value<PointInTimeInterface>
 */
final class Timestamp implements Value
{
    private ?string $value = null;
    private PointInTimeInterface $original;

    public function __construct(PointInTimeInterface $point)
    {
        $this->original = $point;
    }

    public static function unpack(Readable $stream): self
    {
        $time = UnsignedLongLongInteger::unpack($stream)
            ->original()
            ->value();

        return new self(new PointInTime(
            \date((new ISO8601)->toString(), $time),
        ));
    }

    public function original(): PointInTimeInterface
    {
        return $this->original;
    }

    public function pack(): string
    {
        return $this->value ?? $this->value = (new UnsignedLongLongInteger(
            new Integer((int) $this->original->format(new TimestampFormat)),
        ))->pack();
    }
}
