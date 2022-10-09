<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Str,
    Sequence as Seq,
    Map,
    Monoid\Concat,
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

    public static function unpack(Readable $stream): self
    {
        $length = UnsignedLongInteger::unpack($stream)->original();
        $position = $stream->position()->toInt();
        $boundary = $position + $length;

        /** @var Map<string, Value> */
        $map = Map::of();

        while ($position < $boundary) {
            $key = ShortString::unpack($stream)->original();
            $chunk = $stream
                ->read(1)
                ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
                ->filter(static fn($chunk) => $chunk->length() === 1)
                ->match(
                    static fn($chunk) => $chunk,
                    static fn() => throw new \LogicException,
                );

            $map = ($map)(
                $key->toString(),
                Symbols::unpack($chunk->toString(), $stream),
            );

            $position = $stream->position()->toInt();
        }

        return new self($map);
    }

    /**
     * @return Map<string, Value>
     */
    public function original(): Map
    {
        return $this->original;
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
                Str::of(Symbols::symbol(\get_class($pair[1]))),
                $pair[1]->pack(),
            ))
            ->fold(new Concat)
            ->toEncoding('ASCII');

        /** @psalm-suppress ArgumentTypeCoercion */
        $value = UnsignedLongInteger::of($data->length())->pack();

        return $value->append($data->toString());
    }
}
