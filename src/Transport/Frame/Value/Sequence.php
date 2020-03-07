<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\UnboundedTextCannotBeWrapped,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence as Seq;
use function Innmind\Immutable\join;

/**
 * It's an array, but "array" is a reserved keyword in PHP
 *
 * @implements Value<Seq<Value>>
 */
final class Sequence implements Value
{
    private ?string $value = null;
    /** @var Seq<Value> */
    private Seq $original;

    public function __construct(Value ...$values)
    {
        /** @var Seq<Value> */
        $values = Seq::of(Value::class, ...$values);

        $texts = $values->filter(static function(Value $value): bool {
            return $value instanceof Text;
        });

        if ($texts->size() > 0) {
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
            $class = Symbols::class($stream->read(1)->toString());
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
        if (\is_null($this->value)) {
            /** @var Seq<string> */
            $data = $this
                ->original
                ->reduce(
                    Seq::strings(),
                    static function(Seq $carry, Value $value): Seq {
                        return $carry
                            ->add(Symbols::symbol(\get_class($value)))
                            ->add($value->pack());
                    }
                );
            $data = join('', $data)->toEncoding('ASCII');
            $this->value = (new UnsignedLongInteger(
                new Integer($data->length())
            ))->pack();
            $this->value .= $data->toString();
        }

        return $this->value;
    }
}
