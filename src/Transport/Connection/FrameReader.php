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
use Innmind\Stream\Readable;
use Innmind\Filesystem\Stream\StringStream;

final class FrameReader
{
    public function __invoke(Readable $stream, Protocol $protocol): Frame
    {
        $octet = UnsignedOctet::fromStream($stream);

        try {
            $type = Type::fromInt($octet->original()->value());
        } catch (UnknownFrameType $e) {
            throw new NoFrameDetected(new StringStream(
                (string) $stream->read()->prepend((string) $octet)
            ));
        }

        $channel = new Channel(
            UnsignedShortInteger::fromStream($stream)->original()->value()
        );
        $payload = $stream
            ->read(UnsignedLongInteger::fromStream($stream)->original()->value())
            ->toEncoding('ASCII');

        if (
            (
                $type === Type::method() ||
                $type === Type::header()
            ) &&
            $payload->length() < 4
        ) {
            throw new PayloadTooShort;
        }

        $end = UnsignedOctet::fromStream($stream)->original()->value();

        if ($end !== Frame::end()) {
            throw new ReceivedFrameNotDelimitedCorrectly;
        }

        $payload = new StringStream((string) $payload);

        switch ($type) {
            case Type::method():
                $method = new Method(
                    UnsignedShortInteger::fromStream($payload)
                        ->original()
                        ->value(),
                    UnsignedShortInteger::fromStream($payload)
                        ->original()
                        ->value()
                );

                return Frame::method(
                    $channel,
                    $method,
                    ...$protocol->read($method, $payload)
                );

            case Type::header():
                $class = UnsignedShortInteger::fromStream($payload)
                    ->original()
                    ->value();
                $payload->read(2); // walk over the weight definition

                return Frame::header(
                    $channel,
                    $class,
                    ...$protocol->readHeader($payload)
                );

            case Type::body():
                return Frame::body($channel, $payload->read());

            case Type::heartbeat():
                return Frame::heartbeat();

            default:
                throw new LogicException; //if reached then there's an implementation error
        }
    }
}
