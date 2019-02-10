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

final class Table implements Value
{
    private $value;
    private $original;

    /**
     * @param MapInterface<string, Value> $map
     */
    public function __construct(MapInterface $map)
    {
        if (
            (string) $map->keyType() !== 'string' ||
            (string) $map->valueType() !== Value::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type MapInterface<string, %s>',
                Value::class
            ));
        }

        $texts = $map->filter(static function(string $key, Value $value): bool {
            return $value instanceof Text;
        });

        if ($texts->size() > 0) {
            throw new UnboundedTextCannotBeWrapped;
        }

        $data = $map
            ->reduce(
                new Seq,
                static function(Seq $sequence, string $key, Value $value): Seq {
                    return $sequence
                        ->add(new ShortString(new Str($key)))
                        ->add(Symbols::symbol(get_class($value)))
                        ->add($value);
                }
            )
            ->join('')
            ->toEncoding('ASCII');

        $this->value = (string) new UnsignedLongInteger(
            new Integer($data->length())
        );
        $this->value .= $data;
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
        return $this->value;
    }
}
