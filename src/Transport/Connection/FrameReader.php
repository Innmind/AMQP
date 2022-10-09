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
        $octet = UnsignedOctet::unpack($stream)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );

        try {
            $type = Type::of($octet->original());
        } catch (\UnhandledMatchError $e) {
            $data = $stream->read()->match(
                static fn($data) => $data,
                static fn() => throw new \LogicException,
            );

            throw new NoFrameDetected(Stream::ofContent(
                $data->prepend($octet->pack()->toString())->toString(),
            ));
        }

        $channel = new Channel(
            UnsignedShortInteger::unpack($stream)->match(
                static fn($value) => $value->original(),
                static fn() => throw new \LogicException,
            ),
        );
        $payloadLength = UnsignedLongInteger::unpack($stream)->match(
            static fn($value) => $value->original(),
            static fn() => throw new \LogicException,
        );
        /** @psalm-suppress InvalidArgument */
        $payload = $stream
            ->read($payloadLength)
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

        $end = UnsignedOctet::unpack($stream)->match(
            static fn($value) => $value->original(),
            static fn() => throw new \LogicException,
        );

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
