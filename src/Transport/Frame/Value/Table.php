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

final class Table implements Value
{
    private ?string $value = null;
    private Map $original;

    /**
     * @param Map<string, Value> $map
     */
    public function __construct(Map $map)
    {
        assertMap('string', Value::class, $map, 1);

        $texts = $map->filter(static function(string $key, Value $value): bool {
            return $value instanceof Text;
        });

        if ($texts->size() > 0) {
            throw new UnboundedTextCannotBeWrapped;
        }

        $this->original = $map;
    }

    public static function unpack(Readable $stream): Value
    {
        $length = UnsignedLongInteger::unpack($stream)->original();
        $position = $stream->position()->toInt();
        $boundary = $position + $length->value();

        $map = Map::of('string', Value::class);

        while ($position < $boundary) {
            $key = ShortString::unpack($stream)->original();
            $class = Symbols::class($stream->read(1)->toString());

            $map = $map->put($key->toString(), [$class, 'unpack']($stream));

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
        if (\is_null($this->value)) {
            $data = $this
                ->original
                ->reduce(
                    Seq::strings(),
                    static function(Seq $sequence, string $key, Value $value): Seq {
                        return $sequence
                            ->add((new ShortString(Str::of($key)))->pack())
                            ->add(Symbols::symbol(\get_class($value)))
                            ->add($value->pack());
                    },
                );
            $data = join('', $data)->toEncoding('ASCII');

            $this->value = UnsignedLongInteger::of(
                new Integer($data->length())
            )->pack();
            $this->value .= $data->toString();
        }

        return $this->value;
    }
}
