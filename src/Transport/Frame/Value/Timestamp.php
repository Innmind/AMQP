<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    TimeContinuum\Format\Timestamp as TimestampFormat,
};
use Innmind\TimeContinuum\{
    PointInTimeInterface,
    PointInTime\Earth\PointInTime,
    Format\ISO8601,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;

final class Timestamp implements Value
{
    private ?string $value = null;
    private PointInTimeInterface $original;

    public function __construct(PointInTimeInterface $point)
    {
        $this->original = $point;
    }

    public static function fromStream(Readable $stream): Value
    {
        $time = UnsignedLongLongInteger::fromStream($stream)
            ->original()
            ->value();

        return new self(new PointInTime(
            \date((string) new ISO8601, $time)
        ));
    }

    public function original(): PointInTimeInterface
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value ?? $this->value = (string) new UnsignedLongLongInteger(
            new Integer((int) $this->original->format(new TimestampFormat))
        );
    }
}
