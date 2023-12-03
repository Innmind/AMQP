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
 * @implements Value<int<-32768, 32767>>
 * @psalm-immutable
 */
final class SignedShortInteger implements Value
{
    /** @var int<-32768, 32767> */
    private int $original;

    /**
     * @param int<-32768, 32767> $value
     */
    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     *
     * @param int<-32768, 32767> $value
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
            ->frames(Frame\Chunk::of(2))
            ->one()
            ->map(static function($chunk) {
                /** @var int<-32768, 32767> $value */
                [, $value] = \unpack('s', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value))
            ->map(static fn($value) => Unpacked::of(2, $value));
    }

    /**
     * @return int<-32768, 32767>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::signedShortInteger;
    }

    public function pack(): Str
    {
        return Str::of(\pack('s', $this->original));
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(-32768),
            Integer::of(32767),
        );
    }
}
