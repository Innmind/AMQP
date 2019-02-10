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
use Innmind\Stream\Readable;
use Innmind\Immutable\Str;

final class UnsignedLongInteger implements Value
{
    private static $definitionSet;

    private $value;
    private $original;

    public function __construct(Integer $value)
    {
        if (!self::definitionSet()->contains($value)) {
            throw new OutOfRangeValue($value, self::definitionSet());
        }

        $this->original = $value;
    }

    public static function fromString(Str $string): Value
    {
        $string = $string->toEncoding('ASCII');

        if ($string->length() !== 4) {
            throw new StringNotOfExpectedLength($string, 4);
        }

        [, $value] = unpack('N', (string) $string);

        return new self(new Integer($value));
    }

    public static function fromStream(Readable $stream): Value
    {
        $string = $stream->read(4)->toEncoding('ASCII');

        [, $value] = unpack('N', (string) $string);

        return new self(new Integer($value));
    }

    public static function cut(Str $string): Str
    {
        return $string->toEncoding('ASCII')->substring(0, 4);
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value ?? $this->value = pack('N', $this->original->value());
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = Range::inclusive(
            new Integer(0),
            new Integer(4294967295)
        );
    }
}
