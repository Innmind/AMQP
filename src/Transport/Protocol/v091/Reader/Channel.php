<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\Arguments,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Protocol\v091\Methods,
    Exception\UnknownMethod
};
use Innmind\Immutable\{
    Str,
    StreamInterface
};

final class Channel
{
    /**
     * @return StreamInterface<Value>
     */
    public function __invoke(Method $method, Str $arguments): StreamInterface
    {
        switch (true) {
            case Methods::get('channel.open-ok')->equals($method):
                $visit = $this->openOk();
                break;

            case Methods::get('channel.flow')->equals($method):
                $visit = $this->flow();
                break;

            case Methods::get('channel.flow-ok')->equals($method):
                $visit = $this->flowOk();
                break;


            case Methods::get('channel.close')->equals($method):
                $visit = $this->close();
                break;

            case Methods::get('channel.close-ok')->equals($method):
                $visit = $this->closeOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $visit($arguments);
    }

    private function openOk(): Arguments
    {
        return new Arguments; //no arguments
    }

    private function flow(): Arguments
    {
        return new Arguments(
            Bits::class //active
        );
    }

    private function flowOk(): Arguments
    {
        return new Arguments(
            Bits::class //active
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
