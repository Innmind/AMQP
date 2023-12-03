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
 * @implements Value<int<0, 65535>>
 * @psalm-immutable
 */
final class UnsignedShortInteger implements Value
{
    /** @var int<0, 65535> */
    private int $original;

    /**
     * @param int<0, 65535> $value
     */
    private function __construct(int $value)
    {
        $this->original = $value;
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @param int<0, 65535> $value
     */
    public static function internal(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 65535> $value
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
            ->frames(Frame\Chunk::of(2))
            ->one()
            ->filter(static fn($chunk) => $chunk->length() === 2)
            ->map(static function($chunk) {
                /** @var int<0, 65535> $value */
                [, $value] = \unpack('n', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value));
    }

    /**
     * @return int<0, 65535>
     */
    public function original(): int
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::unsignedShortInteger;
    }

    public function pack(): Str
    {
        return Str::of(\pack('n', $this->original));
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Range::inclusive(
            Integer::of(0),
            Integer::of(65535),
        );
    }
}
