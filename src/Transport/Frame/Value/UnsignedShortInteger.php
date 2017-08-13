<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue
};

final class UnsignedShortInteger implements Value
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 65535) {
            throw new OutOfRangeValue($value, 0, 65535);
        }

        $this->value = pack('n', $value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
