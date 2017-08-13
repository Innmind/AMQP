<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;

final class SignedLongLongInteger implements Value
{
    private $value;

    public function __construct(int $value)
    {
        $this->value = pack('q', $value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
