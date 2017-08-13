<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue
};

final class SignedLongInteger implements Value
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < -2147483648 || $value > 2147483647) {
            throw new OutOfRangeValue($value, -2147483648, 2147483647);
        }

        $this->value = pack('l', $value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
