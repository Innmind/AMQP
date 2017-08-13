<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;

final class Bits implements Value
{
    private $value;

    public function __construct(bool ...$bits)
    {
        $value = 0;

        foreach ($bits as $i => $bit) {
            $bit = (int) $bit;
            $value |= $bit << $i;
        }

        $this->value = chr($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
