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
 * @implements Value<int<0, 4294967295>>
 * @psalm-immutable
 */
final class UnsignedLongInteger implements Value
{
    /** @var int<0, 4294967295> */
    private int $original;

    /**
     * @param int<0, 4294967295> $value
     */
    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @param int<0, 4294967295> $value
     */
    public static function internal(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 4294967295> $value
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
                /** @var int<0, 4294967295> $value */
                [, $value] = \unpack('N', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value))
            ->map(static fn($value) => Unpacked::of(4, $value));
    }

    /**
     * @return int<0, 4294967295>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::unsignedLongInteger;
    }

    public function pack(): Str
    {
        return Str::of(\pack('N', $this->original));
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(0),
            Integer::of(4294967295),
        );
    }
}
