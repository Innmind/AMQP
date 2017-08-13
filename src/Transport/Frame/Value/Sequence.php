<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Immutable\Sequence as Seq;

/**
 * It's an array, but "array" is a reserved keyword in PHP
 */
final class Sequence implements Value
{
    private $value;

    public function __construct(Value ...$values)
    {
        $data = (new Seq(...$values))->join('');
        $this->value = (string) new UnsignedLongInteger(
            $data->toEncoding('ASCII')->length()
        );
        $this->value .= $data;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
