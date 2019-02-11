<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Number,
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\NaturalNumbers,
};
use Innmind\Stream\Readable;

final class Decimal implements Value
{
    private static $definitionSet;

    private $value;
    private $original;

    public function __construct(Integer $value, Integer $scale)
    {
        $this->value = (string) new UnsignedOctet($scale);
        $this->value .= (string) new SignedLongInteger($value);
        $this->original = $value->divideBy(
            (new Integer(10))->power($scale)
        );
    }

    public static function fromStream(Readable $stream): Value
    {
        $scale = UnsignedOctet::fromStream($stream)->original();
        $value = SignedLongInteger::fromStream($stream)->original();

        return new self($value, $scale);
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
