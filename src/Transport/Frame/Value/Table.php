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
};
use function Innmind\Immutable\{
    assertMap,
    join,
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
        assertMap('string', Value::class, $map, 1);

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
        $map = Map::of('string', Value::class);

        while ($position < $boundary) {
            $key = ShortString::unpack($stream)->original();
            $class = Symbols::class($stream->read(1)->toString());
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
        $data = $this->original->toSequenceOf(
            'string',
            static function(string $key, Value $value): \Generator {
                yield (new ShortString(Str::of($key)))->pack();
                yield Symbols::symbol(\get_class($value));
                yield $value->pack();
            },
        );
        $data = join('', $data)->toEncoding('ASCII');

        $value = UnsignedLongInteger::of(
            new Integer($data->length()),
        )->pack();

        return $value.$data->toString();
    }
}
