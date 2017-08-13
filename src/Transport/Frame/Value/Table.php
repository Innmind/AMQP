<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
use Innmind\Immutable\{
    Str,
    Sequence as Seq,
    MapInterface
};

final class Table implements Value
{
    private $value;

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

        $data = $map
            ->reduce(
                new Seq,
                static function(Seq $sequence, string $key, Value $value): Seq {
                    return $sequence
                        ->add(new ShortString(new Str($key)))
                        ->add($value);
                }
            )
            ->join('');

        $this->value = (string) new UnsignedLongInteger(
            $data->toEncoding('ASCII')->length()
        );
        $this->value .= $data;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
