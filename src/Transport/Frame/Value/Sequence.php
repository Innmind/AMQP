<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\TimeContinuum\Clock;
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\{
    Sequence as Seq,
    Monoid\Concat,
    Str,
};

/**
 * It's an array, but "array" is a reserved keyword in PHP
 *
 * @implements Value<Seq<Value>>
 * @psalm-immutable
 */
final class Sequence implements Value
{
    /** @var Seq<Value> */
    private Seq $original;

    /**
     * @param Seq<Value> $values
     */
    private function __construct(Seq $values)
    {
        $this->original = $values;
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
     * @return Frame<Unpacked<self>>
     */
    public static function frame(Clock $clock): Frame
    {
        $self = new self(Seq::of());

        return UnsignedLongInteger::frame()->flatMap(
            static fn($length) => match ($length->unwrap()->original()) {
                0 => Frame\NoOp::of(Unpacked::of($length->read(), $self)),
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
    public function original(): Seq
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::sequence;
    }

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
        return Frame\Chunk::of(1)
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
                false => Frame\NoOp::of($value),
            });
    }
}
