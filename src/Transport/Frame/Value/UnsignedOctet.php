<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\OutOfRangeValue,
};
use Innmind\Math\{
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\Range,
};
use Innmind\Stream\Readable;

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
        $this->original = $octet;
    }

    public static function of(Integer $octet): self
    {
        if (!self::definitionSet()->contains($octet)) {
            throw new OutOfRangeValue($octet, self::definitionSet());
        }

        return new self($octet);
    }

    public static function fromStream(Readable $stream): Value
    {
        [, $octet] = \unpack('C', (string) $stream->read(1));

        return new self(new Integer($octet));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value ?? $this->value = \chr($this->original->value());
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = Range::inclusive(
            new Integer(0),
            new Integer(255)
        );
    }
}
