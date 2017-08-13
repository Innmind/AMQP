<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Immutable\Str;

final class LongString implements Value
{
    private $value;

    public function __construct(Str $string)
    {
        $this->value = (string) new UnsignedLongInteger(
            $string->toEncoding('ASCII')->length()
        );
        $this->value .= $string;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
