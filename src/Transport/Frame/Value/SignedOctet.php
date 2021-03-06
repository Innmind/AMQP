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
 * Same as shortshort
 *
 * @implements Value<Integer>
 */
final class SignedOctet implements Value
{
    private static ?Set $definitionSet = null;

    private Integer $original;

    public function __construct(Integer $octet)
    {
        $this->original = $octet;
    }

    public static function of(Integer $value): self
    {
        self::definitionSet()->accept($value);

        return new self($value);
    }

    public static function unpack(Readable $stream): self
    {
        /** @var int $value */
        [, $value] = \unpack('c', $stream->read(1)->toString());

        return new self(new Integer($value));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \pack('c', $this->original->value());
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = Range::inclusive(
            new Integer(-128),
            new Integer(127),
        );
    }
}
