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
 * @implements Value<int<-2147483648, 2147483647>>
 * @psalm-immutable
 */
final class SignedLongInteger implements Value
{
    /** @var int<-2147483648, 2147483647> */
    private int $original;

    /**
     * @param int<-2147483648, 2147483647> $value
     */
    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     *
     * @param int<-2147483648, 2147483647> $value
     */
    public static function of(int $value): self
    {
        self::definitionSet()->accept(Integer::of($value));

        return new self($value);
    }

    /**
     * @param Stream<Client> $stream
     *
     * @return Maybe<Unpacked<self>>
     */
    public static function unpack(Stream $stream): Maybe
    {
        return $stream
            ->frames(Frame\Chunk::of(4))
            ->one()
            ->map(static function($chunk) {
                /** @var int<-2147483648, 2147483647> $value */
                [, $value] = \unpack('l', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value))
            ->map(static fn($value) => Unpacked::of(4, $value));
    }

    /**
     * @return int<-2147483648, 2147483647>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::signedLongInteger;
    }

    public function pack(): Str
    {
        return Str::of(\pack('l', $this->original));
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(-2147483648),
            Integer::of(2147483647),
        );
    }
}
