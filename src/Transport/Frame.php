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
    StreamInterface,
    Str
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
        $this->values = new Stream(Value::class);

        $values = new Sequence(...$values);
        $payload = $values->join('')->toEncoding('ASCII');

        $frame = new Sequence(
            new UnsignedOctet(new Integer($type->toInt())),
            new UnsignedShortInteger(new Integer($channel->toInt())),
            new UnsignedLongInteger(new Integer($payload->length())),
            $payload
        );
        $this->string = (string) $frame
            ->add(new UnsignedOctet(new Integer(self::end())))
            ->join('');
    }

    public static function method(
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
            $self->values,
            static function(Stream $stream, Value $value): Stream {
                return $stream->add($value);
            }
        );

        return $self;
    }

    public static function header(
        Channel $channel,
        int $class,
        Value ...$values
    ): self {
        $self = new self(
            Type::header(),
            $channel,
            new UnsignedShortInteger(new Integer($class)),
            new UnsignedShortInteger(new Integer(0)), //weight
            ...$values
        );
        $self->values = (new Sequence(...$values))->reduce(
            $self->values,
            static function(Stream $stream, Value $value): Stream {
                return $stream->add($value);
            }
        );

        return $self;
    }

    public static function body(Channel $channel, Str $payload): self
    {
        $self = new self(
            Type::body(),
            $channel,
            $value = new Text($payload)
        );
        $self->values = $self->values->add($value);

        return $self;
    }

    public static function heartbeat(): self
    {
        return new self(
            Type::heartbeat(),
            new Channel(0)
        );
    }

    public function type(): Type
    {
        return $this->type;
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function is(Method $method): bool
    {
        if ($this->type() !== Type::method()) {
            return false;
        }

        return $this->method->equals($method);
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

    public static function end(): int
    {
        return 0xCE;
    }
}
