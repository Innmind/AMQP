<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\Transport\Frame\{
    Type,
    Channel,
    Method,
    Value,
    Value\UnsignedOctet,
    Value\UnsignedShortInteger,
    Value\UnsignedLongInteger,
    Value\Text
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Sequence,
    Stream,
    StreamInterface
};

final class Frame
{
    private $type;
    private $channel;
    private $method;
    private $values;
    private $string;

    private function __construct(
        Type $type,
        Channel $channel,
        Value ...$values
    ) {
        $this->type = $type;
        $this->channel = $channel;

        $values = new Sequence(...$values);
        $payload = $values->join('')->toEncoding('ASCII');

        $frame = new Sequence(
            new UnsignedOctet(new Integer($type->toInt())),
            new UnsignedShortInteger(new Integer($channel->toInt())),
            new UnsignedLongInteger(new Integer($payload->length())),
            ...$values
        );
        $this->string = (string) $frame
            ->add(new UnsignedOctet(new Integer(0xCE)))
            ->join('');
    }

    public static function command(
        Channel $channel,
        Method $method,
        Value ...$values
    ): self {
        $self = new self(
            Type::method(),
            $channel,
            new UnsignedShortInteger(new Integer($method->class())),
            new UnsignedShortInteger(new Integer($method->method())),
            ...$values
        );
        $self->method = $method;
        $self->values = (new Sequence(...$values))->reduce(
            new Stream(Value::class),
            static function(Stream $stream, Value $value): Stream {
                return $stream->add($value);
            }
        );

        return $self;
    }

    public function type(): Type
    {
        return $this->type;
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function method(): Method
    {
        return $this->method;
    }

    /**
     * @return StreamInterface<Value>
     */
    public function values(): StreamInterface
    {
        return $this->values;
    }

    public function __toString(): string
    {
        return $this->string;
    }
}
