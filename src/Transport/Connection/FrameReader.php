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
use Innmind\IO\Readable\Stream;
use Innmind\Socket\Client;
use Innmind\Immutable\Maybe;

/**
 * @internal
 */
final class FrameReader
{
    /**
     * @param Stream<Client> $stream
     *
     * @return Maybe<Frame>
     */
    public function __invoke(Stream $stream, Protocol $protocol): Maybe
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
     * @param Stream<Client> $stream
     *
     * @return Maybe<Frame>
     */
    private function readFrame(
        Type $type,
        Channel $channel,
        Stream $stream,
        Protocol $protocol,
    ): Maybe {
        /** @psalm-suppress InvalidArgument */
        return UnsignedLongInteger::unpack($stream->unwrap())
            ->map(static fn($value) => $value->original())
            ->flatMap(fn($length) => match ($type) {
                Type::method => $this->readMethod($stream, $protocol, $channel),
                Type::header => $this->readHeader($stream, $protocol, $channel),
                Type::body => $this->readBody($stream, $channel, $length),
                Type::heartbeat => Maybe::just(Frame::heartbeat()),
            })
            ->flatMap(
                static fn($frame) => UnsignedOctet::unpack($stream->unwrap())
                    ->map(static fn($end) => $end->original())
                    ->filter(static fn($end) => $end === Frame::end())
                    ->map(static fn() => $frame),
            );
    }

    /**
     * @param Stream<Client> $stream
     *
     * @return Maybe<Type>
     */
    private function readType(Stream $stream): Maybe
    {
        return UnsignedOctet::unpack($stream->unwrap())
            ->map(static fn($octet) => $octet->original())
            ->flatMap(Type::maybe(...));
    }

    /**
     * @param Stream<Client> $stream
     *
     * @return Maybe<Channel>
     */
    private function readChannel(Stream $stream): Maybe
    {
        return UnsignedShortInteger::unpack($stream->unwrap())
            ->map(static fn($value) => $value->original())
            ->map(static fn($value) => new Channel($value));
    }

    /**
     * @param Stream<Client> $payload
     *
     * @return Maybe<Frame>
     */
    private function readMethod(
        Stream $payload,
        Protocol $protocol,
        Channel $channel,
    ): Maybe {
        return UnsignedShortInteger::unpack($payload->unwrap())
            ->map(static fn($value) => $value->original())
            ->flatMap(
                static fn($class) => UnsignedShortInteger::unpack($payload->unwrap())
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
     * @param Stream<Client> $payload
     *
     * @return Maybe<Frame>
     */
    private function readHeader(
        Stream $payload,
        Protocol $protocol,
        Channel $channel,
    ): Maybe {
        return UnsignedShortInteger::unpack($payload->unwrap())
            ->map(static fn($value) => $value->original())
            ->flatMap(MethodClass::maybe(...))
            ->flatMap(
                static fn($class) => $payload
                    ->unwrap()
                    ->read(2) // walk over the weight definition
                    ->map(static fn() => $class),
            )
            ->flatMap(
                static fn($class) => $protocol
                    ->readHeader($payload)
                    ->map(static fn($arguments) => Frame::header(
                        $channel,
                        $class,
                        ...$arguments->toList(),
                    )),
            );
    }

    /**
     * @param Stream<Client> $payload
     * @param int<0, 4294967295> $length
     *
     * @return Maybe<Frame>
     */
    private function readBody(
        Stream $payload,
        Channel $channel,
        int $length,
    ): Maybe {
        /** @psalm-suppress InvalidArgument */
        return $payload
            ->unwrap()
            ->read($length)
            ->map(static fn($data) => Frame::body($channel, $data));
    }
}
