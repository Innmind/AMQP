<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\Range,
};
use Innmind\Stream\Readable;

/**
 * @implements Value<Integer>
 */
final class UnsignedShortInteger implements Value
{
    private static ?Set $definitionSet = null;

    private Integer $original;

    public function __construct(Integer $value)
    {
        $this->original = $value;
    }

    public static function of(Integer $value): self
    {
        self::definitionSet()->accept($value);

        return new self($value);
    }

    public static function unpack(Readable $stream): self
    {
        /** @var int $value */
        [, $value] = \unpack('n', $stream->read(2)->toString());

        return new self(new Integer($value));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \pack('n', $this->original->value());
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = Range::inclusive(
            new Integer(0),
            new Integer(65535),
        );
    }
}
