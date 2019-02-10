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
    StreamInterface,
    Stream,
};

/**
 * It's an array, but "array" is a reserved keyword in PHP
 */
final class Sequence implements Value
{
    private $value;
    private $original;

    public function __construct(Value ...$values)
    {
        $sequence = new Seq(...$values);

        $texts = $sequence->filter(static function(Value $value): bool {
            return $value instanceof Text;
        });

        if ($texts->size() > 0) {
            throw new UnboundedTextCannotBeWrapped;
        }

        $data = $sequence
            ->reduce(
                new Seq,
                static function(Seq $carry, Value $value): Seq {
                    return $carry
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
        $this->original = $sequence->reduce(
            new Stream(Value::class),
            static function(Stream $stream, Value $value): Stream {
                return $stream->add($value);
            }
        );
    }

    public static function fromStream(Readable $stream): Value
    {
        $length = UnsignedLongInteger::fromStream($stream)->original();
        $position = $stream->position()->toInt();
        $boundary = $position + $length->value();

        $values = [];

        while ($position < $boundary) {
            $class = Symbols::class((string) $stream->read(1));
            $values[] = [$class, 'fromStream']($stream);
            $position = $stream->position()->toInt();
        }

        return new self(...$values);
    }

    /**
     * @return StreamInterface<Value>
     */
    public function original(): StreamInterface
    {
        return $this->original;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
