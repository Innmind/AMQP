<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    TimeContinuum\Format\Timestamp as TimestampFormat
};
use Innmind\TimeContinuum\PointInTimeInterface;

final class Timestamp implements Value
{
    private $value;

    public function __construct(PointInTimeInterface $point)
    {
        $this->value = (string) new UnsignedLongLongInteger(
            (int) $point->format(new TimestampFormat)
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
