<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    TimeContinuum\Format\Timestamp as TimestampFormat
};
use Innmind\TimeContinuum\{
    PointInTimeInterface,
    PointInTime\Earth\PointInTime,
    Format\ISO8601
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

final class Timestamp implements Value
{
    private $value;
    private $original;

    public function __construct(PointInTimeInterface $point)
    {
        $this->value = (string) new UnsignedLongLongInteger(
            new Integer((int) $point->format(new TimestampFormat))
        );
        $this->original = $point;
    }

    public static function fromString(Str $string): Value
    {
        $time = UnsignedLongLongInteger::fromString($string)
            ->original()
            ->value();

        return new self(new PointInTime(
            date((string) new ISO8601, $time)
        ));
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

    public static function cut(Str $string): Str
    {
        return UnsignedLongLongInteger::cut($string);
    }

    public function original(): PointInTimeInterface
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
