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
    Value\UnsignedLongInteger
};
use Innmind\Immutable\{
    Sequence,
    Stream,
    StreamInterface
};

final class Frame
{
    private $type;
    private $values;
    private $string;

    public function __construct(
        Type $type,
        Channel $channel,
        Method $method,
        Value ...$values
    ) {
        $this->type = $type;
        $this->channel = $channel;
        $this->method = $method;
        $values = new Sequence(...$values);
        $payload = $values->join('')->toEncoding('ASCII');
        $frame = new Sequence(
            new UnsignedOctet($type->toInt()),
            new UnsignedShortInteger($channel->toInt()),
            new UnsignedLongInteger(
                $payload->length() + 4 // 4 is the size for the method
            ),
            new UnsignedShortInteger($method->class()),
            new UnsignedShortInteger($method->method()),
            $payload,
            new UnsignedOctet(0xCE)
        );
        $this->values = $values->reduce(
            new Stream(Value::class),
            static function(Stream $stream, Value $value): Stream {
                return $stream->add($value);
            }
        );
        $this->string = (string) $frame->join('');
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
