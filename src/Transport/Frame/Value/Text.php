<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Immutable\Str;

final class Text implements Value
{
    private $value;

    public function __construct(Str $string)
    {
        $this->value = (string) $string;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
