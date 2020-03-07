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
 * Same as unsigned shortshort
 *
 * @implements Value<Integer>
 */
final class UnsignedOctet implements Value
{
    private static ?Set $definitionSet = null;

    private Integer $original;

    public function __construct(Integer $octet)
    {
        $this->original = $octet;
    }

    public static function of(Integer $octet): self
    {
        self::definitionSet()->accept($octet);

        return new self($octet);
    }

    public static function unpack(Readable $stream): self
    {
        /** @var int $octet */
        [, $octet] = \unpack('C', $stream->read(1)->toString());

        return new self(new Integer($octet));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \chr($this->original->value());
    }

    public static function definitionSet(): Set
    {
        return self::$definitionSet ?? self::$definitionSet = Range::inclusive(
            new Integer(0),
            new Integer(255),
        );
    }
}
