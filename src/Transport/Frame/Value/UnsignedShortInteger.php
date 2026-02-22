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
 * @implements Value<int<0, 65535>>
 * @psalm-immutable
 */
final class UnsignedShortInteger implements Value
{
    /**
     * @param int<0, 65535> $original
     */
    private function __construct(private int $original)
    {
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
        return Frame::chunk(2)
            ->strict()
            ->map(static function($chunk) {
                /**
                 * @psalm-suppress PossiblyInvalidArrayAccess Todo apply a predicate
                 * @var int<0, 65535> $value
                 */
                [, $value] = \unpack('n', $chunk->toString());

                return $value;
            })
            ->map(static fn($value) => new self($value))
            ->map(static fn($value) => Unpacked::of(2, $value));
    }

    /**
     * @return int<0, 65535>
     */
    #[\Override]
    public function original(): int
    {
        return $this->original;
    }

    #[\Override]
    public function symbol(): Symbol
    {
        return Symbol::unsignedShortInteger;
    }

    #[\Override]
    public function pack(): Str
    {
        return Str::of(\pack('n', $this->original));
    }

    /**
     * @psalm-pure
     */
    public static function definitionSet(): Set
    {
        return Set::inclusiveRange(
            Number::of(0),
            Number::of(65535),
        );
    }
}
