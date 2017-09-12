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
    Exception\LogicException
};
use Innmind\Stream\Readable;

final class FrameReader
{
    public function __invoke(Readable $stream, Protocol $protocol): Frame
    {
        $octet = $stream->read(1);

        try {
            $type = Type::fromInt(
                UnsignedOctet::fromString($octet)->original()->value()
            );
        } catch (UnknownFrameType $e) {
            throw new NoFrameDetected($octet->append((string) $stream->read()));
        }

        $channel = new Channel(
            UnsignedShortInteger::fromString($stream->read(2))->original()->value()
        );
        $payload = $stream
            ->read(UnsignedLongInteger::fromString($stream->read(4))->original()->value())
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

        $end = $stream->read(1)->toEncoding('ASCII');

        if ($end->length() !== 1) {
            throw new ReceivedFrameNotDelimitedCorrectly;
        }

        $end = UnsignedOctet::fromString($end)->original()->value();

        if ($end !== Frame::end()) {
            throw new ReceivedFrameNotDelimitedCorrectly;
        }

        switch ($type) {
            case Type::method():
                $method = $payload->substring(0, 4);
                $method = new Method(
                    UnsignedShortInteger::fromString($method->substring(0, 2))
                        ->original()
                        ->value(),
                    UnsignedShortInteger::fromString($method->substring(2, 4))
                        ->original()
                        ->value()
                );
                $arguments = $payload->substring(4);

                return Frame::command(
                    $channel,
                    $method,
                    ...$protocol->read($method, $arguments)
                );

            case Type::header():
                $header = $payload->substring(0, 4); //3 and 4 are the weight
                $class = UnsignedShortInteger::fromString($header->substring(0, 2))
                    ->original()
                    ->value();

                return Frame::header(
                    $channel,
                    $class,
                    ...$protocol->readHeader($payload->substring(4))
                );

            case Type::body():
                return Frame::body($channel, $payload);

            case Type::heartbeat():
                return Frame::heartbeat();

            default:
                throw new LogicException; //if reached then there's an implementation error
        }
    }
}
