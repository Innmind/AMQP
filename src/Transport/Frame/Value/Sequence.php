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
    Sequence as Seq,
    Monoid\Concat,
    Str,
};

/**
 * It's an array, but "array" is a reserved keyword in PHP
 *
 * @implements Value<Seq<Value>>
 */
final class Sequence implements Value
{
    /** @var Seq<Value> */
    private Seq $original;

    /**
     * @no-named-arguments
     */
    public function __construct(Value ...$values)
    {
        $values = Seq::of(...$values);

        $texts = $values->filter(static function(Value $value): bool {
            return $value instanceof Text;
        });

        if (!$texts->empty()) {
            throw new UnboundedTextCannotBeWrapped;
        }

        $this->original = $values;
    }

    public static function unpack(Readable $stream): self
    {
        $length = UnsignedLongInteger::unpack($stream)->original();
        $position = $stream->position()->toInt();
        $boundary = $position + $length->value();

        /** @var list<Value> */
        $values = [];

        while ($position < $boundary) {
            $chunk = $stream->read(1)->match(
                static fn($chunk) => $chunk,
                static fn() => throw new \LogicException,
            );
            $class = Symbols::class($chunk->toString());
            /** @var Value */
            $value = [$class, 'unpack']($stream);
            $values[] = $value;
            $position = $stream->position()->toInt();
        }

        return new self(...$values);
    }

    /**
     * @return Seq<Value>
     */
    public function original(): Seq
    {
        return $this->original;
    }

    public function pack(): string
    {
        $data = $this
            ->original
            ->flatMap(static fn($value) => Seq::of(
                Symbols::symbol(\get_class($value)),
                $value->pack(),
            ))
            ->map(Str::of(...))
            ->fold(new Concat)
            ->toEncoding('ASCII');
        $value = (new UnsignedLongInteger(
            Integer::of($data->length()),
        ))->pack();

        return $value.$data->toString();
    }
}
