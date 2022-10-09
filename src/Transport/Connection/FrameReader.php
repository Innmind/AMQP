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
            UnsignedShortInteger::unpack($payload)->original(),
            UnsignedShortInteger::unpack($payload)->original(),
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
    }

    private function readBody(Readable $payload, Channel $channel): Frame
    {
        return Frame::body($channel, $payload->read()->match(
            static fn($data) => $data,
            static fn() => throw new \LogicException,
        ));
    }
}
