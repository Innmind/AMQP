<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\TimeContinuum\Clock;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Sequence as Seq,
    Monoid\Concat,
    Str,
    Maybe,
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
     * @return Maybe<self>
     */
    public static function unpack(Clock $clock, Readable $stream): Maybe
    {
        /** @var Seq<Value> */
        $values = Seq::of();

        return UnsignedLongInteger::unpack($stream)
            ->map(static fn($length) => $length->original())
            ->flatMap(static fn($length) => match ($length) {
                0 => Maybe::just($values),
                default => self::unpackNested(
                    $clock,
                    $length + $stream->position()->toInt(),
                    $stream,
                    $values,
                ),
            })
            ->map(static fn($values) => new self($values));
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
     * @param Seq<Value> $values
     *
     * @return Maybe<Seq<Value>>
     */
    private static function unpackNested(
        Clock $clock,
        int $boundary,
        Readable $stream,
        Seq $values,
    ): Maybe {
        return $stream
            ->read(1)
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->filter(static fn($chunk) => $chunk->length() === 1)
            ->flatMap(static fn($chunk) => Symbol::unpack($clock, $chunk->toString(), $stream))
            ->flatMap(static fn($value) => match ($stream->position()->toInt() < $boundary) {
                true => self::unpackNested(
                    $clock,
                    $boundary,
                    $stream,
                    ($values)($value),
                ),
                false => Maybe::just(($values)($value)),
            });
    }
}
