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
    Exception\UnknownFrameType,
    Exception\NoFrameDetected,
    Exception\LogicException,
};
use Innmind\Stream\{
    Readable,
    Readable\Stream,
};
use function Innmind\Immutable\unwrap;

final class FrameReader
{
    public function __invoke(Readable $stream, Protocol $protocol): Frame
    {
        $octet = UnsignedOctet::unpack($stream);

        try {
            $type = Type::of($octet->original()->value());
        } catch (UnknownFrameType $e) {
            throw new NoFrameDetected(Stream::ofContent(
                $stream->read()->prepend($octet->pack())->toString(),
            ));
        }

        $channel = new Channel(
            UnsignedShortInteger::unpack($stream)->original()->value(),
        );
        $payload = $stream
            ->read(UnsignedLongInteger::unpack($stream)->original()->value())
            ->toEncoding('ASCII');

        if (
            (
                $type === Type::method() ||
                $type === Type::header()
            ) &&
            $payload->length() < 4
        ) {
            throw new PayloadTooShort((string) $payload->length());
        }

        $end = UnsignedOctet::unpack($stream)->original()->value();

        if ($end !== Frame::end()) {
            throw new ReceivedFrameNotDelimitedCorrectly;
        }

        $payload = Stream::ofContent($payload->toString());

        switch ($type) {
            case Type::method():
                $method = new Method(
                    UnsignedShortInteger::unpack($payload)
                        ->original()
                        ->value(),
                    UnsignedShortInteger::unpack($payload)
                        ->original()
                        ->value(),
                );

                return Frame::method(
                    $channel,
                    $method,
                    ...unwrap($protocol->read($method, $payload)),
                );

            case Type::header():
                $class = UnsignedShortInteger::unpack($payload)
                    ->original()
                    ->value();
                $payload->read(2); // walk over the weight definition

                return Frame::header(
                    $channel,
                    $class,
                    ...unwrap($protocol->readHeader($payload)),
                );

            case Type::body():
                return Frame::body($channel, $payload->read());

            case Type::heartbeat():
                return Frame::heartbeat();

            default:
                throw new LogicException((string) $type->toInt()); // if reached then there's an implementation error
        }
    }
}
