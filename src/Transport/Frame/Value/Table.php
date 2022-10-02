<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\UnboundedTextCannotBeWrapped,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Str,
    Sequence as Seq,
    Map,
    Monoid\Concat,
};

/**
 * @implements Value<Map<string, Value>>
 */
final class Table implements Value
{
    /** @var Map<string, Value> */
    private Map $original;

    /**
     * @param Map<string, Value> $map
     */
    public function __construct(Map $map)
    {
        $texts = $map->filter(static function(string $_, Value $value): bool {
            return $value instanceof Text;
        });

        if (!$texts->empty()) {
            throw new UnboundedTextCannotBeWrapped;
        }

        $this->original = $map;
    }

    public static function unpack(Readable $stream): self
    {
        $length = UnsignedLongInteger::unpack($stream)->original();
        $position = $stream->position()->toInt();
        $boundary = $position + $length->value();

        /** @var Map<string, Value> */
        $map = Map::of();

        while ($position < $boundary) {
            $key = ShortString::unpack($stream)->original();
            $chunk = $stream->read(1)->match(
                static fn($chunk) => $chunk,
                static fn() => throw new \LogicException,
            );
            $class = Symbols::class($chunk->toString());
            /** @var Value */
            $value = [$class, 'unpack']($stream);

            $map = ($map)($key->toString(), $value);

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

    public function pack(): string
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $data = $this
            ->original
            ->reduce(
                Seq::strings(),
                static fn(Seq $data, string $key, Value $value) => ($data)
                    ((new ShortString(Str::of($key)))->pack())
                    (Symbols::symbol(\get_class($value)))
                    ($value->pack()),
            )
            ->map(Str::of(...))
            ->fold(new Concat)
            ->toEncoding('ASCII');

        $value = UnsignedLongInteger::of(
            Integer::of($data->length()),
        )->pack();

        return $value.$data->toString();
    }
}
