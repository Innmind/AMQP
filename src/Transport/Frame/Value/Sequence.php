<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\{
    Frame\Value,
    Protocol\ArgumentTranslator,
};
use Innmind\TimeContinuum\Clock;
use Innmind\IO\Frame;
use Innmind\Immutable\{
    Sequence as Seq,
    Monoid\Concat,
    Str,
    Maybe,
    Either,
    Predicate\Instance,
};

/**
 * It's an array, but "array" is a reserved keyword in PHP
 *
 * @implements Value<Seq<Value>>
 * @psalm-immutable
 */
final class Sequence implements Value
{
    /**
     * @param Seq<Value> $original
     */
    private function __construct(private Seq $original)
    {
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    public static function of(Value ...$values): self
    {
        return new self(Seq::of(...$values));
    }

    /**
     * @psalm-pure
     *
     * @return Either<mixed, Value>
     */
    public static function wrap(ArgumentTranslator $translate, mixed $value): Either
    {
        return Maybe::of($value)
            ->keep(Instance::of(Seq::class))
            ->map(static fn($values) => $values->map($translate))
            ->either()
            ->map(static fn($values) => new self($values))
            ->leftMap(static fn(): mixed => $value);
    }

    /**
     * @psalm-pure
     *
     * @return Frame<Unpacked<self>>
     */
    public static function frame(Clock $clock): Frame
    {
        $self = new self(Seq::of());

        return UnsignedLongInteger::frame()->flatMap(
            static fn($length) => match ($length->unwrap()->original()) {
                0 => Frame::just(Unpacked::of($length->read(), $self)),
                default => self::unpackNested(
                    $clock,
                    Unpacked::of($length->read(), $self),
                    $length->unwrap()->original(),
                ),
            },
        );
    }

    /**
     * @return Seq<Value>
     */
    #[\Override]
    public function original(): Seq
    {
        return $this->original;
    }

    #[\Override]
    public function symbol(): Symbol
    {
        return Symbol::sequence;
    }

    #[\Override]
    public function pack(): Str
    {
        $data = $this
            ->original
            ->flatMap(static fn($value) => Seq::of(
                $value->symbol()->pack(),
                $value->pack(),
            ))
            ->fold(new Concat)
            ->toEncoding(Str\Encoding::ascii);
        /** @psalm-suppress InvalidArgument */
        $value = UnsignedLongInteger::of($data->length())->pack();

        return $value->append($data->toString());
    }

    /**
     * @param Unpacked<self> $unpacked
     *
     * @return Frame<Unpacked<self>>
     */
    private static function unpackNested(
        Clock $clock,
        Unpacked $unpacked,
        int $length,
    ): Frame {
        return Frame::chunk(1)
            ->strict()
            ->flatMap(static fn($chunk) => Symbol::frame($clock, $chunk->toString()))
            ->map(static fn($value) => Unpacked::of(
                $unpacked->read() + $value->read() + 1,
                new self(($unpacked->unwrap()->original)($value->unwrap())),
            ))
            ->flatMap(static fn($value) => match ($value->read() < $length) {
                true => self::unpackNested(
                    $clock,
                    $value,
                    $length,
                ),
                false => Frame::just($value),
            });
    }
}
