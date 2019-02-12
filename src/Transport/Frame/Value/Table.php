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
    MapInterface,
    Map,
};
use function Innmind\Immutable\assertMap;

final class Table implements Value
{
    private $value;
    private $original;

    /**
     * @param MapInterface<string, Value> $map
     */
    public function __construct(MapInterface $map)
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

        $map = new Map('string', Value::class);

        while ($position < $boundary) {
            $key = ShortString::fromStream($stream)->original();
            $class = Symbols::class((string) $stream->read(1));

            $map = $map->put((string) $key, [$class, 'fromStream']($stream));

            $position = $stream->position()->toInt();
        }

        return new self($map);
    }

    /**
     * @return MapInterface<string, Value>
     */
    public function original(): MapInterface
    {
        return $this->original;
    }

    public function __toString(): string
    {
        if (\is_null($this->value)) {
            $data = $this
                ->original
                ->reduce(
                    new Seq,
                    static function(Seq $sequence, string $key, Value $value): Seq {
                        return $sequence
                            ->add(new ShortString(new Str($key)))
                            ->add(Symbols::symbol(\get_class($value)))
                            ->add($value);
                    }
                )
                ->join('')
                ->toEncoding('ASCII');

            $this->value = (string) UnsignedLongInteger::of(
                new Integer($data->length())
            );
            $this->value .= $data;
        }

        return $this->value;
    }
}
