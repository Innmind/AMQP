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
 * @psalm-immutable
 */
final class UnsignedOctet implements Value
{
    private Integer $original;

    public function __construct(Integer $octet)
    {
        $this->original = $octet;
    }

    /**
     * @psalm-pure
     */
    public static function of(Integer $octet): self
    {
        self::definitionSet()->accept($octet);

        return new self($octet);
    }

    public static function unpack(Readable $stream): self
    {
        $chunk = $stream->read(1)->match(
            static fn($chunk) => $chunk,
            static fn() => throw new \LogicException,
        );

        /** @var int $octet */
        [, $octet] = \unpack('C', $chunk->toString());

        return new self(Integer::of($octet));
    }

    public function original(): Integer
    {
        return $this->original;
    }

    public function pack(): string
    {
        return \chr($this->original->value());
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(0),
            Integer::of(255),
        );
    }
}
