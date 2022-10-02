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
        $chunk = $stream->read(1)->match(
            static fn($chunk) => $chunk,
            static fn() => throw new \LogicException,
        );
        /** @var int $value */
        [, $value] = \unpack('c', $chunk->toString());

        return new self(Integer::of($value));
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
            Integer::of(-128),
            Integer::of(127),
        );
    }
}
