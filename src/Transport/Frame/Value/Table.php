<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\TimeContinuum\Clock;
use Innmind\IO\Readable\{
    Stream,
    Frame,
};
use Innmind\Socket\Client;
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
     * @param Stream<Client> $stream
     *
     * @return Maybe<Unpacked<self>>
     */
    public static function unpack(Clock $clock, Stream $stream): Maybe
    {
        $self = new self(Map::of());

        return UnsignedLongInteger::unpack($stream)->flatMap(
            static fn($length) => match ($length->unwrap()->original()) {
                0 => Maybe::just(Unpacked::of($length->read(), $self)),
                default => self::unpackNested(
                    $clock,
                    Unpacked::of($length->read(), $self),
                    $length->unwrap()->original(),
                    $stream,
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
     * @param Unpacked<self> $unpacked
     * @param Stream<Client> $stream
     *
     * @return Maybe<Unpacked<self>>
     */
    private static function unpackNested(
        Clock $clock,
        Unpacked $unpacked,
        int $length,
        Stream $stream,
    ): Maybe {
        return ShortString::unpack($stream)
            ->flatMap(
                static fn($key) => $stream
                    ->frames(Frame\Chunk::of(1))
                    ->one()
                    ->flatMap(static fn($chunk) => Symbol::unpack(
                        $clock,
                        $chunk->toString(),
                        $stream,
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
                    $stream,
                ),
                false => Maybe::just($value),
            });
    }
}
