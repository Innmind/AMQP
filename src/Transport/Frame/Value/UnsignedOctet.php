<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue,
    Exception\StringNotOfExpectedLength
};
use Innmind\Math\{
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\Range
};
use Innmind\Immutable\Str;

/**
 * Same as unsigned shortshort
 */
final class UnsignedOctet implements Value
{
    private static $definitionSet;

    private $value;
    private $original;

    public function __construct(Integer $octet)
    {
        if (!self::definitionSet()->contains($octet)) {
            throw new OutOfRangeValue($octet, self::definitionSet());
        }

        $this->value = chr($octet->value());
        $this->original = $octet;
    }

    public static function fromString(Str $string): Value
    {
        $string = $string->toEncoding('ASCII');

        if ($string->length() !== 1) {
            throw new StringNotOfExpectedLength($string, 1);
        }

        [, $octet] = unpack('C', (string) $string);

        return new self(new Integer($octet));
    }

    public static function cut(Str $string): Str
    {
        return $string->toEncoding('ASCII')->substring(0, 1);
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = Range::inclusive(
            new Integer(0),
            new Integer(255)
        );
    }
}
