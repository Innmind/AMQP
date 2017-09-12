<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value,
    Exception\StringNotOfExpectedLength,
    Exception\UnboundedTextCannotBeWrapped
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Sequence as Seq,
    StreamInterface,
    Stream,
    Str
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

    public static function fromString(Str $string): Value
    {
        $string = $string->toEncoding('ASCII');
        $length = UnsignedLongInteger::fromString($string->substring(0, 4))->original();
        $string = $string->substring(4);

        if ($string->length() !== $length->value()) {
            throw new StringNotOfExpectedLength($string, $length->value());
        }

        $values = [];

        while ($string->length() !== 0) {
            $class = Symbols::class((string) $string->substring(0, 1));
            $element = [$class, 'cut']($string->substring(1))->toEncoding('ASCII');
            $values[] = [$class, 'fromString']($element);
            $string = $string->substring($element->length() + 1);
        }

        return new self(...$values);
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
