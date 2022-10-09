<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\Value;
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
 * @psalm-immutable
 */
final class Sequence implements Value
{
    /** @var Seq<Value> */
    private Seq $original;

    /**
     * @no-named-arguments
     */
    private function __construct(Value ...$values)
    {
        $this->original = Seq::of(...$values);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    public static function of(Value ...$values): self
    {
        return new self(...$values);
    }

    public static function unpack(Readable $stream): self
    {
        $length = UnsignedLongInteger::unpack($stream)->original();
        $position = $stream->position()->toInt();
        $boundary = $position + $length;

        /** @var list<Value> */
        $values = [];

        while ($position < $boundary) {
            $chunk = $stream
                ->read(1)
                ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
                ->filter(static fn($chunk) => $chunk->length() === 1)
                ->match(
                    static fn($chunk) => $chunk,
                    static fn() => throw new \LogicException,
                );
            $values[] = Symbols::unpack($chunk->toString(), $stream);
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
        /** @psalm-suppress ArgumentTypeCoercion */
        $value = UnsignedLongInteger::of($data->length())->pack();

        return $value.$data->toString();
    }
}
