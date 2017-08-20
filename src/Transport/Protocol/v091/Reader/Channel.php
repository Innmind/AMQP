<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\ChunkArguments,
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
                $chunk = $this->openOk();
                break;

            case Methods::get('channel.flow')->equals($method):
                $chunk = $this->flow();
                break;

            case Methods::get('channel.flow-ok')->equals($method):
                $chunk = $this->flowOk();
                break;


            case Methods::get('channel.close')->equals($method):
                $chunk = $this->close();
                break;

            case Methods::get('channel.close-ok')->equals($method):
                $chunk = $this->closeOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $chunk($arguments);
    }

    private function openOk(): ChunkArguments
    {
        return new ChunkArguments; //no arguments
    }

    private function flow(): ChunkArguments
    {
        return new ChunkArguments(
            Bits::class //active
        );
    }

    private function flowOk(): ChunkArguments
    {
        return new ChunkArguments(
            Bits::class //active
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
