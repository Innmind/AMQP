<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\Transport\{
    Protocol,
    Frame,
    Frame\Method,
    Frame\MethodClass,
    Frame\Type,
    Frame\Channel,
    Frame\Value\UnsignedOctet,
    Frame\Value\UnsignedShortInteger,
    Frame\Value\UnsignedLongInteger,
};
use Innmind\Stream\Readable;
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
            ->flatMap(fn($length) => match ($type) {
                Type::method => $this->readMethod($stream, $protocol, $channel),
                Type::header => $this->readHeader($stream, $protocol, $channel),
                Type::body => $this->readBody($stream, $channel, $length),
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
            ->flatMap(
                static fn($method) => $protocol
                    ->read($method, $payload)
                    ->map(static fn($values) => Frame::method(
                        $channel,
                        $method,
                        ...$values->toList(),
                    )),
            );
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
     * @param int<0, 4294967295> $length
     *
     * @return Maybe<Frame>
     */
    private function readBody(
        Readable $payload,
        Channel $channel,
        int $length,
    ): Maybe {
        /** @psalm-suppress InvalidArgument */
        return $payload
            ->read($length)
            ->map(static fn($data) => Frame::body($channel, $data));
    }
}
