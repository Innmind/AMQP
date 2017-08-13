<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue
};

final class UnsignedOctet implements Value
{
    private $value;

    public function __construct(int $octet)
    {
        if ($octet < 0 || $octet > 255) {
            throw new OutOfRangeValue($octet, 0, 255);
        }

        $this->value = chr($octet);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
