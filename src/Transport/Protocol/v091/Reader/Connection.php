<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\Arguments,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Protocol\v091\Methods,
    Exception\UnknownMethod
};
use Innmind\Immutable\{
    Str,
    StreamInterface
};

final class Connection
{
    /**
     * @return StreamInterface<Value>
     */
    public function __invoke(Method $method, Str $arguments): StreamInterface
    {
        switch (true) {
            case Methods::get('connection.start')->equals($method):
                $visit = $this->start();
                break;

            case Methods::get('connection.secure')->equals($method):
                $visit = $this->secure();
                break;

            case Methods::get('connection.tune')->equals($method):
                $visit = $this->tune();
                break;

            case Methods::get('connection.open-ok')->equals($method):
                $visit = $this->openOk();
                break;

            case Methods::get('connection.close')->equals($method):
                $visit = $this->close();
                break;

            case Methods::get('connection.close-ok')->equals($method):
                $visit = $this->closeOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $visit($arguments);
    }

    private function start(): Arguments
    {
        return new Arguments(
            UnsignedOctet::class, //major version
            UnsignedOctet::class, //minor version
            Table::class, //server properties
            LongString::class, //mechanisms
            LongString::class //locales
        );
    }

    private function secure(): Arguments
    {
        return new Arguments(
            LongString::class //challenge
        );
    }

    private function tune(): Arguments
    {
        return new Arguments(
            UnsignedShortInteger::class, //max channels
            UnsignedLongInteger::class, //max frame size
            UnsignedShortInteger::class //heartbeat delay
        );
    }

    private function openOk(): Arguments
    {
        return new Arguments(
            ShortString::class //known hosts
        );
    }

    private function close(): Arguments
    {
        return new Arguments(
            UnsignedShortInteger::class, //reply code
            ShortString::class, //reply text
            UnsignedShortInteger::class, //failing class id
            UnsignedShortInteger::class //failing method id
        );
    }

    private function closeOk(): Arguments
    {
        return new Arguments; // no arguments
    }
}
