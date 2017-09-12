<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue
};
use Innmind\Math\{
    Algebra\Number,
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\NaturalNumbers
};
use Innmind\Immutable\Str;

final class Decimal implements Value
{
    private static $definitionSet;

    private $value;
    private $original;

    public function __construct(Integer $value, Integer $scale)
    {
        if (!self::definitionSet()->contains($scale)) {
            throw new OutOfRangeValue($scale, self::definitionSet());
        }

        $this->value = (string) new UnsignedOctet($scale);
        $this->value .= (string) new SignedLongInteger($value);
        $this->original = $value->divideBy(
            (new Integer(10))->power($scale)
        );
    }

    public static function fromString(Str $string): Value
    {
        $string = $string->toEncoding('ASCII');

        return new self(
            SignedLongInteger::fromString($string->substring(1))->original(),
            UnsignedOctet::fromString($string->substring(0, 1))->original()
        );
    }

    public static function cut(Str $string): Str
    {
        return $string
            ->toEncoding('ASCII')
            ->substring(0, 1)
            ->append(
                (string) SignedLongInteger::cut($string->substring(1))
            );
    }

    public function original(): Number
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = new NaturalNumbers;
    }
}
