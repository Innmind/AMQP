<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\Range,
};
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
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
     * @psalm-pure
     *
     * @return Either<mixed, Value>
     */
    public static function wrap(mixed $value): Either
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return Maybe::of($value)
            ->filter(\is_int(...))
            ->map(Integer::of(...))
            ->filter(self::definitionSet()->contains(...))
            ->either()
            ->map(static fn($int) => new self($int->value()))
            ->leftMap(static fn(): mixed => $value);
    }

    /**
     * @psalm-pure
     *
     * @return Frame<Unpacked<self>>
     */
    public static function frame(): Frame
    {
        return Frame\Chunk::of(2)
            ->map(static function($chunk) {
                /** @var int<0, 65535> $value */
                [, $value] = \unpack('n', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value))
            ->map(static fn($value) => Unpacked::of(2, $value));
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
