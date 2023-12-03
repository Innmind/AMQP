<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\Range,
};
use Innmind\IO\Readable\{
    Stream,
    Frame,
};
use Innmind\Socket\Client;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * Same as unsigned shortshort
 *
 * @implements Value<int<0, 255>>
 * @psalm-immutable
 */
final class UnsignedOctet implements Value
{
    /** @var int<0, 255> */
    private int $original;

    /**
     * @param int<0, 255> $octet
     */
    private function __construct(int $octet)
    {
        $this->original = $octet;
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @param int<0, 255> $octet
     */
    public static function internal(int $octet): self
    {
        return new self($octet);
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 255> $octet
     */
    public static function of(int $octet): self
    {
        self::definitionSet()->accept(Integer::of($octet));

        return new self($octet);
    }

    /**
     * @param Stream<Client> $stream
     *
     * @return Maybe<self>
     */
    public static function unpack(Stream $stream): Maybe
    {
        return $stream
            ->frames(Frame\Chunk::of(1))
            ->one()
            ->map(static function($chunk) {
                /** @var int<0, 255> $octet */
                [, $octet] = \unpack('C', $chunk->toString());

                return $octet;
            })
            ->map(static fn($octet) => new self($octet));
    }

    /**
     * @return int<0, 255>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::unsignedOctet;
    }

    public function pack(): Str
    {
        return Str::of(\chr($this->original));
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
