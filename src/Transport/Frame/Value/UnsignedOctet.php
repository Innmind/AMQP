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
 * Same as unsigned shortshort
 *
 * @implements Value<int<0, 255>>
 * @psalm-immutable
 */
final class UnsignedOctet implements Value
{
    /**
     * @param int<0, 255> $original
     */
    private function __construct(private int $original)
    {
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
        return Frame::chunk(1)
            ->strict()
            ->map(static function($chunk) {
                /**
                 * @psalm-suppress PossiblyInvalidArrayAccess Todo apply a predicate
                 * @var int<0, 255> $octet
                 */
                [, $octet] = \unpack('C', $chunk->toString());

                return $octet;
            })
            ->map(static fn($octet) => new self($octet))
            ->map(static fn($value) => Unpacked::of(1, $value));
    }

    /**
     * @return int<0, 255>
     */
    #[\Override]
    public function original(): int
    {
        return $this->original;
    }

    #[\Override]
    public function symbol(): Symbol
    {
        return Symbol::unsignedOctet;
    }

    #[\Override]
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
