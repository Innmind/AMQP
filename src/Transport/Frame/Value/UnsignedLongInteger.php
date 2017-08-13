<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue
};

final class UnsignedLongInteger implements Value
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 4294967295) {
            throw new OutOfRangeValue($value, 0, 4294967295);
        }

        $this->value = pack('N', $value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
