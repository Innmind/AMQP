<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Integer,
    DefinitionSet\Set,
    DefinitionSet\Range,
};
use Innmind\IO\Frame;
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

/**
 * @implements Value<int<-2147483648, 2147483647>>
 * @psalm-immutable
 */
final class SignedLongInteger implements Value
{
    /**
     * @param int<-2147483648, 2147483647> $original
     */
    private function __construct(private int $original)
    {
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
        return Frame::chunk(4)
            ->strict()
            ->map(static function($chunk) {
                /**
                 * @psalm-suppress PossiblyInvalidArrayAccess Todo apply a predicate
                 * @var int<-2147483648, 2147483647> $value
                 */
                [, $value] = \unpack('l', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value))
            ->map(static fn($value) => Unpacked::of(4, $value));
    }

    /**
     * @return int<-2147483648, 2147483647>
     */
    #[\Override]
    public function original(): int
    {
        return $this->original;
    }

    #[\Override]
    public function symbol(): Symbol
    {
        return Symbol::signedLongInteger;
    }

    #[\Override]
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
