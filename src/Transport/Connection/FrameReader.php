<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Protocol,
    Transport\Frame,
    Transport\Frame\Method,
    Transport\Frame\MethodClass,
    Transport\Frame\Type,
    Transport\Frame\Channel,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\UnsignedLongInteger,
    Exception\ReceivedFrameNotDelimitedCorrectly,
    Exception\PayloadTooShort,
    Exception\NoFrameDetected,
    Exception\LogicException,
};
use Innmind\Stream\{
    Readable,
    Readable\Stream,
};

final class FrameReader
{
    public function __invoke(Readable $stream, Protocol $protocol): Frame
    {
        $type = UnsignedOctet::unpack($stream)
            ->map(static fn($octet) => $octet->original())
            ->flatMap(Type::maybe(...))
            ->match(
                static fn($type) => $type,
                static fn() => throw new NoFrameDetected,
            );

        $channel = UnsignedShortInteger::unpack($stream)
            ->map(static fn($value) => $value->original())
            ->map(static fn($value) => new Channel($value))
            ->match(
                static fn($channel) => $channel,
                static fn() => throw new \LogicException,
            );
        /** @psalm-suppress InvalidArgument */
        $payload = UnsignedLongInteger::unpack($stream)
            ->map(static fn($value) => $value->original())
            ->flatMap(static fn($length) => $stream->read($length))
            ->map(static fn($payload) => $payload->toEncoding('ASCII'))
            ->filter(static fn($payload) => match ($type) {
                Type::method, Type::header => $payload->length() >= 4,
                default => true,
            })
            ->map(static fn($payload) => $payload->toString())
            ->map(Stream::ofContent(...))
            ->match(
                static fn($payload) => $payload,
                static fn() => throw new PayloadTooShort,
            );

        $_ = UnsignedOctet::unpack($stream)
            ->map(static fn($end) => $end->original())
            ->filter(static fn($end) => $end === Frame::end())
            ->match(
                static fn() => null,
                static fn() => throw new ReceivedFrameNotDelimitedCorrectly,
            );

        return match ($type) {
            Type::method => $this->readMethod($payload, $protocol, $channel),
            Type::header => $this->readHeader($payload, $protocol, $channel),
            Type::body => $this->readBody($payload, $channel),
            Type::heartbeat => Frame::heartbeat(),
        };
    }

    private function readMethod(
        Readable $payload,
        Protocol $protocol,
        Channel $channel,
    ): Frame {
        $method = Method::of(
            UnsignedShortInteger::unpack($payload)->match(
                static fn($value) => $value->original(),
                static fn() => throw new \LogicException,
            ),
            UnsignedShortInteger::unpack($payload)->match(
                static fn($value) => $value->original(),
                static fn() => throw new \LogicException,
            ),
        );

        return Frame::method(
            $channel,
            $method,
            ...$protocol->read($method, $payload)->toList(),
        );
    }

    private function readHeader(
        Readable $payload,
        Protocol $protocol,
        Channel $channel,
    ): Frame {
        $class = UnsignedShortInteger::unpack($payload)->match(
            static fn($value) => $value->original(),
            static fn() => throw new \LogicException,
        );
        $_ = $payload->read(2)->match(
            static fn() => null,
            static fn() => throw new \LogicException,
        ); // walk over the weight definition

        return Frame::header(
            $channel,
            MethodClass::of($class),
            ...$protocol->readHeader($payload)->toList(),
        );
    }

    private function readBody(Readable $payload, Channel $channel): Frame
    {
        return Frame::body($channel, $payload->read()->match(
            static fn($data) => $data,
            static fn() => throw new \LogicException,
        ));
    }
}
