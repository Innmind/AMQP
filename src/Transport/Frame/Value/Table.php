<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\StringNotOfExpectedLength,
    Exception\UnboundedTextCannotBeWrapped
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Str,
    Sequence as Seq,
    MapInterface,
    Map
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

    public static function fromString(Str $string): Value
    {
        $string = $string->toEncoding('ASCII');
        $length = UnsignedLongInteger::fromString($string->substring(0, 4))->original();
        $string = $string->substring(4);

        if ($string->length() !== $length->value()) {
            throw new StringNotOfExpectedLength($string, $length->value());
        }

        $map = new Map('string', Value::class);

        while ($string->length() !== 0) {
            $key = ShortString::cut($string);
            $string = $string->toEncoding('ASCII')->substring($key->length());
            $key = ShortString::fromString($key)->original();

            $class = Symbols::class((string) $string->substring(0, 1));
            $element = [$class, 'cut']($string->substring(1))->toEncoding('ASCII');

            $map = $map->put((string) $key, [$class, 'fromString']($element));

            $string = $string->substring($element->length() + 1);
        }

        return new self($map);
    }

    public static function fromStream(Readable $stream): Value
    {
        $length = UnsignedLongInteger::fromStream($stream)->original();
        $string = $stream->read($length->value());

        $map = new Map('string', Value::class);

        while ($string->length() !== 0) {
            $key = ShortString::cut($string);
            $string = $string->toEncoding('ASCII')->substring($key->length());
            $key = ShortString::fromString($key)->original();

            $class = Symbols::class((string) $string->substring(0, 1));
            $element = [$class, 'cut']($string->substring(1))->toEncoding('ASCII');

            $map = $map->put((string) $key, [$class, 'fromString']($element));

            $string = $string->substring($element->length() + 1);
        }

        return new self($map);
    }

    public static function cut(Str $string): Str
    {
        $string = $string->toEncoding('ASCII');
        $length = UnsignedLongInteger::fromString(
            UnsignedLongInteger::cut($string)
        )->original();

        return $string->substring(0, $length->value() + 4);
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
