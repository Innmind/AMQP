<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\TimeContinuum\Clock;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Str,
    Sequence as Seq,
    Map,
    Monoid\Concat,
    Maybe,
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
     * @return Maybe<self>
     */
    public static function unpack(Clock $clock, Readable $stream): Maybe
    {
        /** @var Map<string, Value> */
        $values = Map::of();

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
            ->map(static fn($map) => new self($map));
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
            ->toEncoding('ASCII');

        /** @psalm-suppress InvalidArgument */
        $value = UnsignedLongInteger::of($data->length())->pack();

        return $value->append($data->toString());
    }

    /**
     * @param Map<string, Value> $values
     *
     * @return Maybe<Map<string, Value>>
     */
    private static function unpackNested(
        Clock $clock,
        int $boundary,
        Readable $stream,
        Map $values,
    ): Maybe {
        return ShortString::unpack($stream)
            ->map(static fn($key) => $key->original()->toString())
            ->flatMap(
                static fn($key) => $stream
                    ->read(1)
                    ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
                    ->filter(static fn($chunk) => $chunk->length() === 1)
                    ->flatMap(static fn($chunk) => Symbol::unpack(
                        $clock,
                        $chunk->toString(),
                        $stream,
                    ))
                    ->map(static fn($value) => ($values)($key, $value)),
            )
            ->flatMap(static fn($values) => match ($stream->position()->toInt() < $boundary) {
                true => self::unpackNested(
                    $clock,
                    $boundary,
                    $stream,
                    $values,
                ),
                false => Maybe::just($values),
            });
    }
}
