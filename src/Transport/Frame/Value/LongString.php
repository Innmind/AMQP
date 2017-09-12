<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\StringNotOfExpectedLength
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\Str;

final class LongString implements Value
{
    private $value;
    private $original;

    public function __construct(Str $string)
    {
        $this->original = $string;
        $string = $string->toEncoding('ASCII');
        $this->value = (string) new UnsignedLongInteger(
            new Integer($string->length())
        );
        $this->value .= $string;
    }

    public static function fromString(Str $string): Value
    {
        $string = $string->toEncoding('ASCII');
        $length = UnsignedLongInteger::fromString($string->substring(0, 4))->original();
        $string = $string->substring(4);

        if ($string->length() !== $length->value()) {
            throw new StringNotOfExpectedLength($string, $length->value());
        }

        return new self($string);
    }

    public static function cut(Str $string): Str
    {
        $string = $string->toEncoding('ASCII');
        $length = UnsignedLongInteger::fromString($string->substring(0, 4))->original();

        return $string->substring(0, $length->value() + 4);
    }

    public function original(): Str
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
