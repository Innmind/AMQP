<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue
};

final class UnsignedLongLongInteger implements Value
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new OutOfRangeValue($value, 0, PHP_INT_MAX);
        }

        $this->value = pack('J', $value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
