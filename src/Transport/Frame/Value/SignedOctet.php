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
 * Same as shortshort
 *
 * @implements Value<int<-128, 127>>
 * @psalm-immutable
 */
final class SignedOctet implements Value
{
    /** @var int<-128, 127> */
    private int $original;

    /**
     * @param int<-128, 127> $octet
     */
    private function __construct(int $octet)
    {
        $this->original = $octet;
    }

    /**
     * @psalm-pure
     *
     * @param int<-128, 127> $value
     */
    public static function of(int $value): self
    {
        self::definitionSet()->accept(Integer::of($value));

        return new self($value);
    }

    /**
     * @param Stream<Client> $stream
     *
     * @return Maybe<self>
     */
    public static function unpack(Stream $stream): Maybe
    {
        return $stream
            ->toEncoding(Str\Encoding::ascii)
            ->frames(Frame\Chunk::of(1))
            ->one()
            ->filter(static fn($chunk) => $chunk->length() === 1)
            ->map(static function($chunk) {
                /** @var int<-128, 127> $value */
                [, $value] = \unpack('c', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value));
    }

    /**
     * @return int<-128, 127>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::signedOctet;
    }

    public function pack(): Str
    {
        return Str::of(\pack('c', $this->original));
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(-128),
            Integer::of(127),
        );
    }
}
