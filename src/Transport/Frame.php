<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport;

use Innmind\AMQP\Transport\Frame\{
    Type,
    Channel,
    Method,
    MethodClass,
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
    Monoid\Concat,
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
    /** @var Maybe<Str> */
    private Maybe $body;

    /**
     * @no-named-arguments
     */
    private function __construct(
        Type $type,
        Channel $channel,
        Value ...$values,
    ) {
        $this->type = $type;
        $this->channel = $channel;
        /** @var Maybe<Method> */
        $this->method = Maybe::nothing();
        $this->values = Sequence::of(...$values);
        /** @var Maybe<Str> */
        $this->body = Maybe::nothing();
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
            UnsignedShortInteger::internal($method->class()->toInt()),
            UnsignedShortInteger::internal($method->method()),
            ...$values,
        );
        $self->method = Maybe::just($method);

        return $self;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     */
    public static function header(
        Channel $channel,
        MethodClass $class,
        Value ...$values,
    ): self {
        return new self(
            Type::header,
            $channel,
            UnsignedShortInteger::internal($class->toInt()),
            UnsignedShortInteger::internal(0), // weight
            ...$values,
        );
    }

    /**
     * @psalm-pure
     */
    public static function body(Channel $channel, Str $payload): self
    {
        $self = new self(Type::body, $channel);
        $self->body = Maybe::just($payload);

        return $self;
    }

    /**
     * @psalm-pure
     */
    public static function heartbeat(): self
    {
        return new self(Type::heartbeat, new Channel(0));
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
        // only Type::method and Type::header have values and the first 2 are
        // the values describing the method or the class so we drop them here
        // because the outside user doesn't need to access them this way
        return $this->values->drop(2);
    }

    /**
     * @return Maybe<Str> The content of a Type::body frame
     */
    public function content(): Maybe
    {
        return $this->body;
    }

    public function pack(): Str
    {
        return match ($this->type) {
            Type::method => $this->packMethod(),
            Type::header => $this->packHeader(),
            Type::body => $this->packBody(),
            Type::heartbeat => $this->packHeartbeat(),
        };
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

    private function packMethod(): Str
    {
        $payload = $this
            ->values
            ->map(static fn($value) => $value->pack())
            ->fold(new Concat)
            ->toEncoding('ASCII');

        return $this->doPack($payload);
    }

    private function packHeader(): Str
    {
        $payload = $this
            ->values
            ->map(static fn($value) => $value->pack())
            ->fold(new Concat)
            ->toEncoding('ASCII');

        return $this->doPack($payload);
    }

    private function packBody(): Str
    {
        $body = $this->body->match(
            static fn($body) => $body,
            static fn() => throw new \LogicException('Type::body set without a body'),
        );

        return $this->doPack($body);
    }

    private function packHeartbeat(): Str
    {
        return $this->doPack(Str::of(''));
    }

    private function doPack(Str $payload): Str
    {
        $payload = $payload->toEncoding('ASCII');

        /** @psalm-suppress ArgumentTypeCoercion */
        return Sequence::of(
            UnsignedOctet::internal($this->type->toInt())->pack(),
            UnsignedShortInteger::internal($this->channel->toInt())->pack(),
            UnsignedLongInteger::internal($payload->length())->pack(),
            $payload,
            UnsignedOctet::internal(self::end())->pack(),
        )
            ->fold(new Concat)
            ->toEncoding('ASCII');
    }
}
