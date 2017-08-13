<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue
};

final class SignedShortInteger implements Value
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < -32768 || $value > 32767) {
            throw new OutOfRangeValue($value, -32768, 32767);
        }

        $this->value = pack('s', $value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
