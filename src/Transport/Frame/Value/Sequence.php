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
 */
final class Sequence implements Value
{
    private ?string $value = null;
    private Seq $original;

    public function __construct(Value ...$values)
    {
        $values = Seq::of(Value::class, ...$values);

        $texts = $values->filter(static function(Value $value): bool {
            return $value instanceof Text;
        });

        if ($texts->size() > 0) {
            throw new UnboundedTextCannotBeWrapped;
        }

        $this->original = $values;
    }

    public static function fromStream(Readable $stream): Value
    {
        $length = UnsignedLongInteger::fromStream($stream)->original();
        $position = $stream->position()->toInt();
        $boundary = $position + $length->value();

        $values = [];

        while ($position < $boundary) {
            $class = Symbols::class($stream->read(1)->toString());
            $values[] = [$class, 'fromStream']($stream);
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

    public function __toString(): string
    {
        if (\is_null($this->value)) {
            $data = $this
                ->original
                ->reduce(
                    Seq::strings(),
                    static function(Seq $carry, Value $value): Seq {
                        return $carry
                            ->add(Symbols::symbol(\get_class($value)))
                            ->add((string) $value);
                    }
                );
            $data = join('', $data)->toEncoding('ASCII');
            $this->value = (string) new UnsignedLongInteger(
                new Integer($data->length())
            );
            $this->value .= $data->toString();
        }

        return $this->value;
    }
}
