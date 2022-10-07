<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\ChunkArguments,
    Transport\Frame\Value,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedShortInteger,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class Channel
{
    /**
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
    {
        /** @psalm-suppress UnhandledMatchCondition todo regroup everything in the Reader class */
        $chunk = match ($method) {
            Method::channelOpenOk => $this->openOk(),
            Method::channelFlow => $this->flow(),
            Method::channelFlowOk => $this->flowOk(),
            Method::channelClose => $this->close(),
            Method::channelCloseOk => $this->closeOk(),
        };

        return $chunk($arguments);
    }

    private function openOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function flow(): ChunkArguments
    {
        return new ChunkArguments(
            Bits::class, // active
        );
    }

    private function flowOk(): ChunkArguments
    {
        return new ChunkArguments(
            Bits::class, // active
        );
    }

    private function close(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedShortInteger::class, // reply code
            ShortString::class, // reply text
            UnsignedShortInteger::class, // failing class id
            UnsignedShortInteger::class, // failing method id
        );
    }

    private function closeOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }
}
