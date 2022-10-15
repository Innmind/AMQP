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
};
use Innmind\Stream\{
    Readable,
    Readable\Stream,
};
use Innmind\Immutable\Maybe;

final class FrameReader
{
    /**
     * @return Maybe<Frame>
     */
    public function __invoke(Readable $stream, Protocol $protocol): Maybe
    {
        return $this
            ->readType($stream)
            ->flatMap(
                fn($type) => $this
                    ->readChannel($stream)
                    ->flatMap(fn($channel) => $this->readFrame(
                        $type,
                        $channel,
                        $stream,
                        $protocol,
                    )),
            );
    }

    /**
     * @return Maybe<Frame>
     */
    private function readFrame(
        Type $type,
        Channel $channel,
        Readable $stream,
        Protocol $protocol,
    ): Maybe {
        /** @psalm-suppress InvalidArgument */
        return UnsignedLongInteger::unpack($stream)
            ->map(static fn($value) => $value->original())
            ->flatMap(static fn($length) => $stream->read($length))
            ->map(static fn($payload) => $payload->toEncoding('ASCII'))
            ->filter(static fn($payload) => match ($type) {
                Type::method, Type::header => $payload->length() >= 4,
                default => true,
            })
            ->map(static fn($payload) => $payload->toString())
            ->map(Stream::ofContent(...))
            ->flatMap(fn($payload) => match ($type) {
                Type::method => $this->readMethod($payload, $protocol, $channel),
                Type::header => $this->readHeader($payload, $protocol, $channel),
                Type::body => $this->readBody($payload, $channel),
                Type::heartbeat => Maybe::just(Frame::heartbeat()),
            })
            ->flatMap(
                static fn($frame) => UnsignedOctet::unpack($stream)
                    ->map(static fn($end) => $end->original())
                    ->filter(static fn($end) => $end === Frame::end())
                    ->map(static fn() => $frame),
            );
    }

    /**
     * @return Maybe<Type>
     */
    private function readType(Readable $stream): Maybe
    {
        return UnsignedOctet::unpack($stream)
            ->map(static fn($octet) => $octet->original())
            ->flatMap(Type::maybe(...));
    }

    /**
     * @return Maybe<Channel>
     */
    private function readChannel(Readable $stream): Maybe
    {
        return UnsignedShortInteger::unpack($stream)
            ->map(static fn($value) => $value->original())
            ->map(static fn($value) => new Channel($value));
    }

    /**
     * @return Maybe<Frame>
     */
    private function readMethod(
        Readable $payload,
        Protocol $protocol,
        Channel $channel,
    ): Maybe {
        return UnsignedShortInteger::unpack($payload)
            ->map(static fn($value) => $value->original())
            ->flatMap(
                static fn($class) => UnsignedShortInteger::unpack($payload)
                    ->map(static fn($value) => $value->original())
                    ->flatMap(static fn($method) => Method::maybe($class, $method)),
            )
            ->map(static fn($method) => Frame::method(
                $channel,
                $method,
                ...$protocol->read($method, $payload)->toList(),
            ));
    }

    /**
     * @return Maybe<Frame>
     */
    private function readHeader(
        Readable $payload,
        Protocol $protocol,
        Channel $channel,
    ): Maybe {
        return UnsignedShortInteger::unpack($payload)
            ->map(static fn($value) => $value->original())
            ->flatMap(MethodClass::maybe(...))
            ->flatMap(
                static fn($class) => $payload
                    ->read(2) // walk over the weight definition
                    ->map(static fn() => $class),
            )
            ->map(static fn($class) => Frame::header(
                $channel,
                $class,
                ...$protocol->readHeader($payload)->toList(),
            ));
    }

    /**
     * @return Maybe<Frame>
     */
    private function readBody(Readable $payload, Channel $channel): Maybe
    {
        return $payload
            ->read()
            ->map(static fn($data) => Frame::body($channel, $data));
    }
}
