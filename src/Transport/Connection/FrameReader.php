<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Protocol,
    Transport\Frame,
    Transport\Frame\Method,
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
        $octet = UnsignedOctet::unpack($stream);

        try {
            $type = Type::of($octet->original());
        } catch (\UnhandledMatchError $e) {
            $data = $stream->read()->match(
                static fn($data) => $data,
                static fn() => throw new \LogicException,
            );

            throw new NoFrameDetected(Stream::ofContent(
                $data->prepend($octet->pack())->toString(),
            ));
        }

        $channel = new Channel(
            UnsignedShortInteger::unpack($stream)->original(),
        );
        /** @psalm-suppress InvalidArgument */
        $payload = $stream
            ->read(UnsignedLongInteger::unpack($stream)->original())
            ->match(
                static fn($payload) => $payload,
                static fn() => throw new \LogicException,
            )
            ->toEncoding('ASCII');

        if (
            (
                $type === Type::method ||
                $type === Type::header
            ) &&
            $payload->length() < 4
        ) {
            throw new PayloadTooShort((string) $payload->length());
        }

        $end = UnsignedOctet::unpack($stream)->original();

        if ($end !== Frame::end()) {
            throw new ReceivedFrameNotDelimitedCorrectly;
        }

        $payload = Stream::ofContent($payload->toString());

        switch ($type) {
            case Type::method:
                $method = Method::of(
                    UnsignedShortInteger::unpack($payload)->original(),
                    UnsignedShortInteger::unpack($payload)->original(),
                );

                return Frame::method(
                    $channel,
                    $method,
                    ...$protocol->read($method, $payload)->toList(),
                );

            case Type::header:
                $class = UnsignedShortInteger::unpack($payload)->original();
                $_ = $payload->read(2)->match(
                    static fn() => null,
                    static fn() => throw new \LogicException,
                ); // walk over the weight definition

                return Frame::header(
                    $channel,
                    $class,
                    ...$protocol->readHeader($payload)->toList(),
                );

            case Type::body:
                return Frame::body($channel, $payload->read()->match(
                    static fn($data) => $data,
                    static fn() => throw new \LogicException,
                ));

            case Type::heartbeat:
                return Frame::heartbeat();

            default:
                throw new LogicException((string) $type->toInt()); // if reached then there's an implementation error
        }
    }
}
