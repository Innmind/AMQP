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
    Maybe,
};

final class Frame
{
    private Type $type;
    private Channel $channel;
    /** @var Maybe<Method> */
    private Maybe $method;
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
        /** @var Maybe<Method> */
        $this->method = Maybe::nothing();
        /** @var Sequence<Value> */
        $this->values = Sequence::of();

        $values = \array_map(
            static fn(Value $value): string => $value->pack(),
            $values,
        );
        $payload = Str::of(\implode('', $values))->toEncoding('ASCII');

        $frame = \array_map(
            static fn(Value $value): string => $value->pack(),
            [
                new UnsignedOctet(Integer::of($type->toInt())),
                new UnsignedShortInteger(Integer::of($channel->toInt())),
                new UnsignedLongInteger(Integer::of($payload->length())),
                new Text($payload),
                new UnsignedOctet(Integer::of(self::end())),
            ],
        );
        $this->string = \implode('', $frame);
    }

    /**
     * @no-named-arguments
     */
    public static function method(
        Channel $channel,
        Method $method,
        Value ...$values,
    ): self {
        $self = new self(
            Type::method,
            $channel,
            new UnsignedShortInteger(Integer::of($method->class())),
            new UnsignedShortInteger(Integer::of($method->method())),
            ...$values,
        );
        $self->method = Maybe::just($method);
        $self->values = Sequence::of(...$values);

        return $self;
    }

    /**
     * @no-named-arguments
     */
    public static function header(
        Channel $channel,
        int $class,
        Value ...$values,
    ): self {
        $self = new self(
            Type::header,
            $channel,
            new UnsignedShortInteger(Integer::of($class)),
            new UnsignedShortInteger(Integer::of(0)), // weight
            ...$values,
        );
        $self->values = Sequence::of(...$values);

        return $self;
    }

    public static function body(Channel $channel, Str $payload): self
    {
        $self = new self(
            Type::body,
            $channel,
            $value = new Text($payload),
        );
        /** @var Sequence<Value> */
        $self->values = Sequence::of($value);

        return $self;
    }

    public static function heartbeat(): self
    {
        return new self(
            Type::heartbeat,
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
        return $this->method->match(
            static fn($self) => $self->equals($method),
            static fn() => false,
        );
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
