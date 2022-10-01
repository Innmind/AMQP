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
    Value\Text,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Sequence,
    Str,
};

final class Frame
{
    private Type $type;
    private Channel $channel;
    private ?Method $method = null;
    /** @var Sequence<Value> */
    private Sequence $values;
    private string $string;

    private function __construct(
        Type $type,
        Channel $channel,
        Value ...$values,
    ) {
        $this->type = $type;
        $this->channel = $channel;
        /** @var Sequence<Value> */
        $this->values = Sequence::of(Value::class);

        $values = \array_map(
            static fn(Value $value): string => $value->pack(),
            $values,
        );
        $payload = Str::of(\implode('', $values))->toEncoding('ASCII');

        $frame = \array_map(
            static fn(Value $value): string => $value->pack(),
            [
                new UnsignedOctet(new Integer($type->toInt())),
                new UnsignedShortInteger(new Integer($channel->toInt())),
                new UnsignedLongInteger(new Integer($payload->length())),
                new Text($payload),
                new UnsignedOctet(new Integer(self::end())),
            ],
        );
        $this->string = \implode('', $frame);
    }

    public static function method(
        Channel $channel,
        Method $method,
        Value ...$values,
    ): self {
        $self = new self(
            Type::method(),
            $channel,
            new UnsignedShortInteger(new Integer($method->class())),
            new UnsignedShortInteger(new Integer($method->method())),
            ...$values,
        );
        $self->method = $method;
        $self->values = Sequence::of(Value::class, ...$values);

        return $self;
    }

    public static function header(
        Channel $channel,
        int $class,
        Value ...$values,
    ): self {
        $self = new self(
            Type::header(),
            $channel,
            new UnsignedShortInteger(new Integer($class)),
            new UnsignedShortInteger(new Integer(0)), // weight
            ...$values,
        );
        $self->values = Sequence::of(Value::class, ...$values);

        return $self;
    }

    public static function body(Channel $channel, Str $payload): self
    {
        $self = new self(
            Type::body(),
            $channel,
            $value = new Text($payload),
        );
        /** @var Sequence<Value> */
        $self->values = Sequence::of(Value::class, $value);

        return $self;
    }

    public static function heartbeat(): self
    {
        return new self(
            Type::heartbeat(),
            new Channel(0),
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

        return $this->method && $this->method->equals($method);
    }

    /**
     * @return Sequence<Value>
     */
    public function values(): Sequence
    {
        return $this->values;
    }

    public function toString(): string
    {
        return $this->string;
    }

    public static function end(): int
    {
        return 0xCE;
    }
}
