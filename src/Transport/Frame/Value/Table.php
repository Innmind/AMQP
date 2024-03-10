<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\{
    Frame\Value,
    Protocol\ArgumentTranslator,
};
use Innmind\TimeContinuum\Clock;
use Innmind\IO\Readable\Frame;
use Innmind\Immutable\{
    Str,
    Sequence as Seq,
    Maybe,
    Either,
    Map,
    Monoid\Concat,
    Predicate\Instance,
};

/**
 * @implements Value<Map<string, Value>>
 * @psalm-immutable
 */
final class Table implements Value
{
    /** @var Map<string, Value> */
    private Map $original;

    /**
     * @param Map<string, Value> $map
     */
    private function __construct(Map $map)
    {
        $this->original = $map;
    }

    /**
     * @psalm-pure
     *
     * @param Map<string, Value> $map
     */
    public static function of(Map $map): self
    {
        return new self($map);
    }

    /**
     * @psalm-pure
     *
     * @return Either<mixed, Value>
     */
    public static function wrap(ArgumentTranslator $translate, mixed $value): Either
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return Maybe::of($value)
            ->keep(Instance::of(Map::class))
            ->flatMap(
                static fn($map) => $map->reduce(
                    Maybe::just(Map::of()),
                    static fn(Maybe $translated, $key, $value) => $translated->flatMap(
                        static fn(Map $map) => ShortString::wrap($key)
                            ->maybe()
                            ->map(
                                static fn() => ($map)($key, $translate($value)),
                            ),
                    ),
                ),
            )
            ->either()
            ->map(static fn($map) => new self($map))
            ->leftMap(static fn(): mixed => $value);
    }

    /**
     * @psalm-pure
     *
     * @return Frame<Unpacked<self>>
     */
    public static function frame(Clock $clock): Frame
    {
        $self = new self(Map::of());

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
     * @return Map<string, Value>
     */
    public function original(): Map
    {
        return $this->original;
    }

    public function symbol(): Symbol
    {
        return Symbol::table;
    }

    public function pack(): Str
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $data = $this
            ->original
            ->map(static fn($key, $value) => [$key, $value])
            ->values()
            ->flatMap(static fn($pair) => Seq::of(
                ShortString::of(Str::of($pair[0]))->pack(),
                $pair[1]->symbol()->pack(),
                $pair[1]->pack(),
            ))
            ->fold(new Concat)
            ->toEncoding(Str\Encoding::ascii);

        /** @psalm-suppress InvalidArgument */
        $value = UnsignedLongInteger::of($data->length())->pack();

        return $value->append($data->toString());
    }

    /**
     * @psalm-pure
     *
     * @param Unpacked<self> $unpacked
     *
     * @return Frame<Unpacked<self>>
     */
    private static function unpackNested(
        Clock $clock,
        Unpacked $unpacked,
        int $length,
    ): Frame {
        return ShortString::frame()
            ->flatMap(
                static fn($key) => Frame\Chunk::of(1)
                    ->flatMap(static fn($chunk) => Symbol::frame(
                        $clock,
                        $chunk->toString(),
                    ))
                    ->map(static fn($value) => Unpacked::of(
                        $unpacked->read() + $key->read() + $value->read() + 1,
                        new self(($unpacked->unwrap()->original)(
                            $key->unwrap()->original()->toString(),
                            $value->unwrap(),
                        )),
                    )),
            )
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
