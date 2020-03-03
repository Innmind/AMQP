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

    public static function fromStream(Readable $stream): Value
    {
        $length = UnsignedLongInteger::fromStream($stream)->original();
        $position = $stream->position()->toInt();
        $boundary = $position + $length->value();

        $map = Map::of('string', Value::class);

        while ($position < $boundary) {
            $key = ShortString::fromStream($stream)->original();
            $class = Symbols::class($stream->read(1)->toString());

            $map = $map->put($key->toString(), [$class, 'fromStream']($stream));

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

    public function __toString(): string
    {
        if (\is_null($this->value)) {
            $data = $this
                ->original
                ->reduce(
                    Seq::strings(),
                    static function(Seq $sequence, string $key, Value $value): Seq {
                        return $sequence
                            ->add((string) new ShortString(Str::of($key)))
                            ->add(Symbols::symbol(\get_class($value)))
                            ->add((string) $value);
                    },
                );
            $data = join('', $data)->toEncoding('ASCII');

            $this->value = (string) UnsignedLongInteger::of(
                new Integer($data->length())
            );
            $this->value .= $data->toString();
        }

        return $this->value;
    }
}
