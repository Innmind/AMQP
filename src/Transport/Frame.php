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
use Innmind\Immutable\{
    Sequence,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
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

        /** @psalm-suppress ArgumentTypeCoercion */
        $frame = \array_map(
            static fn(Value $value): string => $value->pack(),
            [
                UnsignedOctet::of($type->toInt()),
                UnsignedShortInteger::of($channel->toInt()),
                UnsignedLongInteger::of($payload->length()),
                Text::of($payload),
                UnsignedOctet::of(self::end()),
            ],
        );
        $this->string = \implode('', $frame);
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     */
    public static function method(
        Channel $channel,
        Method $method,
        Value ...$values,
    ): self {
        $self = new self(
            Type::method,
            $channel,
            UnsignedShortInteger::of($method->class()->toInt()),
            UnsignedShortInteger::of($method->method()),
            ...$values,
        );
        $self->method = Maybe::just($method);
        $self->values = Sequence::of(...$values);

        return $self;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @param int<0, 65535> $class
     */
    public static function header(
        Channel $channel,
        int $class,
        Value ...$values,
    ): self {
        $self = new self(
            Type::header,
            $channel,
            UnsignedShortInteger::of($class),
            UnsignedShortInteger::of(0), // weight
            ...$values,
        );
        $self->values = Sequence::of(...$values);

        return $self;
    }

    /**
     * @psalm-pure
     */
    public static function body(Channel $channel, Str $payload): self
    {
        $self = new self(
            Type::body,
            $channel,
            $value = Text::of($payload),
        );
        /** @var Sequence<Value> */
        $self->values = Sequence::of($value);

        return $self;
    }

    /**
     * @psalm-pure
     */
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

    /**
     * @psalm-pure
     *
     * @return 206
     */
    public static function end(): int
    {
        return 0xCE;
    }
}
