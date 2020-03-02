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
 * Same as shortshort
 */
final class SignedOctet implements Value
{
    private static ?Set $definitionSet = null;

    private ?string $value = null;
    private Integer $original;

    public function __construct(Integer $octet)
    {
        $this->original = $octet;
    }

    public static function of(Integer $value): self
    {
        if (!self::definitionSet()->contains($value)) {
            throw new OutOfRangeValue($value, self::definitionSet());
        }

        return new self($value);
    }

    public static function fromStream(Readable $stream): Value
    {
        [, $value] = \unpack('c', (string) $stream->read(1));

        return new self(new Integer($value));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value ?? $this->value = \pack('c', $this->original->value());
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = Range::inclusive(
            new Integer(-128),
            new Integer(127)
        );
    }
}
