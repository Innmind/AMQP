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
    Exception\PayloadTooShort
};
use Innmind\Stream\Readable;

final class FrameReader
{
    public function __invoke(Readable $stream, Protocol $protocol): Frame
    {
        $type = Type::fromInt(
            UnsignedOctet::fromString($stream->read(1))->original()->value()
        );
        $channel = new Channel(
            UnsignedShortInteger::fromString($stream->read(2))->original()->value()
        );
        $payload = $stream
            ->read(UnsignedLongInteger::fromString($stream->read(4))->original()->value())
            ->toEncoding('ASCII');

        if ($payload->length() < 4) {
            throw new PayloadTooShort;
        }

        $end = $stream->read(1)->toEncoding('ASCII');

        if ($end->length() !== 1) {
            throw new ReceivedFrameNotDelimitedCorrectly;
        }

        $end = UnsignedOctet::fromString($end)->original()->value();

        if ($end !== 0xCE) {
            throw new ReceivedFrameNotDelimitedCorrectly;
        }

        $method = $payload->substring(0, 4);
        $method = new Method(
            UnsignedShortInteger::fromString($method->substring(0, 2))->original()->value(),
            UnsignedShortInteger::fromString($method->substring(2, 4))->original()->value()
        );
        $arguments = $payload->substring(4);

        return new Frame(
            $type,
            $channel,
            $method,
            ...$protocol->read($method, $arguments)
        );
    }
}
