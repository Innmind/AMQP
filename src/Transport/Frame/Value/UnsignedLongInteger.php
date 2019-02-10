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

    public static function fromStream(Readable $stream): Value
    {
        [, $value] = unpack('N', (string) $stream->read(4));

        return new self(new Integer($value));
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
