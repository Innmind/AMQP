<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\ChunkArguments,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Protocol\v091\Methods,
    Exception\UnknownMethod,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class Connection
{
    /**
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
    {
        switch (true) {
            case Methods::get('connection.start')->equals($method):
                $chunk = $this->start();
                break;

            case Methods::get('connection.secure')->equals($method):
                $chunk = $this->secure();
                break;

            case Methods::get('connection.tune')->equals($method):
                $chunk = $this->tune();
                break;

            case Methods::get('connection.open-ok')->equals($method):
                $chunk = $this->openOk();
                break;

            case Methods::get('connection.close')->equals($method):
                $chunk = $this->close();
                break;

            case Methods::get('connection.close-ok')->equals($method):
                $chunk = $this->closeOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $chunk($arguments);
    }

    private function start(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedOctet::class, //major version
            UnsignedOctet::class, //minor version
            Table::class, //server properties
            LongString::class, //mechanisms
            LongString::class //locales
        );
    }

    private function secure(): ChunkArguments
    {
        return new ChunkArguments(
            LongString::class //challenge
        );
    }

    private function tune(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedShortInteger::class, //max channels
            UnsignedLongInteger::class, //max frame size
            UnsignedShortInteger::class //heartbeat delay
        );
    }

    private function openOk(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class //known hosts
        );
    }

    private function close(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedShortInteger::class, //reply code
            ShortString::class, //reply text
            UnsignedShortInteger::class, //failing class id
            UnsignedShortInteger::class //failing method id
        );
    }

    private function closeOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }
}
