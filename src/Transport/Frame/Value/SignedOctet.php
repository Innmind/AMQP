<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Math\{
    Algebra\Number,
    DefinitionSet\Set,
};
use Innmind\IO\Frame;
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

/**
 * Same as shortshort
 *
 * @implements Value<int<-128, 127>>
 * @psalm-immutable
 */
final class SignedOctet implements Value
{
    /**
     * @param int<-128, 127> $original
     */
    private function __construct(private int $original)
    {
    }

    /**
     * @psalm-pure
     *
     * @param int<-128, 127> $value
     */
    public static function of(int $value): self
    {
        $_ = self::definitionSet()
            ->accept(Number::of($value))
            ->unwrap();

        return new self($value);
    }

    /**
     * @psalm-pure
     *
     * @return Either<mixed, Value>
     */
    public static function wrap(mixed $value): Either
    {
        /** @psalm-suppress InvalidArgument */
        return Maybe::of($value)
            ->filter(\is_int(...))
            ->map(Number::of(...))
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
                 * @var int<-128, 127> $value
                 */
                [, $value] = \unpack('c', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value))
            ->map(static fn($value) => Unpacked::of(1, $value));
    }

    /**
     * @return int<-128, 127>
     */
    #[\Override]
    public function original(): int
    {
        return $this->original;
    }

    #[\Override]
    public function symbol(): Symbol
    {
        return Symbol::signedOctet;
    }

    #[\Override]
    public function pack(): Str
    {
        return Str::of(\pack('c', $this->original));
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Set::inclusiveRange(
            Number::of(-128),
            Number::of(127),
        );
    }
}
