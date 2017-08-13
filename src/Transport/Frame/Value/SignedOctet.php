<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue
};

final class SignedOctet implements Value
{
    private $value;

    public function __construct(int $octet)
    {
        if ($octet < -128 || $octet > 127) {
            throw new OutOfRangeValue($octet, -128, 127);
        }

        $this->value = pack('c', $octet);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
