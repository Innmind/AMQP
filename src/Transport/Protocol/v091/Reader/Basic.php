<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\ChunkArguments,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Protocol\v091\Methods,
    Exception\UnknownMethod
};
use Innmind\Immutable\{
    Str,
    StreamInterface
};

final class Basic
{
    /**
     * @return StreamInterface<Value>
     */
    public function __invoke(Method $method, Str $arguments): StreamInterface
    {
        switch (true) {
            case Methods::get('basic.qos-ok')->equals($method):
                $chunk = $this->qosOk();
                break;

            case Methods::get('basic.consume-ok')->equals($method):
                $chunk = $this->consumeOk();
                break;

            case Methods::get('basic.cancel-ok')->equals($method):
                $chunk = $this->cancelOk();
                break;

            case Methods::get('basic.return')->equals($method):
                $chunk = $this->return();
                break;

            case Methods::get('basic.deliver')->equals($method):
                $chunk = $this->deliver();
                break;

            case Methods::get('basic.get-ok')->equals($method):
                $chunk = $this->getOk();
                break;

            case Methods::get('basic.get-empty')->equals($method):
                $chunk = $this->getEmpty();
                break;

            case Methods::get('basic.recover-ok')->equals($method):
                $chunk = $this->recoverOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $chunk($arguments);
    }

    private function qosOk(): ChunkArguments
    {
        return new ChunkArguments; //no arguments
    }

    private function consumeOk(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class //consumer tag
        );
    }

    private function cancelOk(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class //consumer tag
        );
    }

    private function return(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedShortInteger::class, //reply code
            ShortString::class, //reply text
            ShortString::class, //exchange
            ShortString::class //routing key
        );
    }

    private function deliver(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class, //consumer tag
            UnsignedLongLongInteger::class, //delivery tag
            Bits::class, //redelivered
            ShortString::class, //exchange
            ShortString::class //routing key
        );
    }

    private function getOk(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedLongLongInteger::class, //delivery tag
            Bits::class, //redelivered
            ShortString::class, //exchange
            ShortString::class, //routing key
            UnsignedLongInteger::class //message count
        );
    }

    private function getEmpty(): ChunkArguments
    {
        return new ChunkArguments; //no arguments
    }

    private function recoverOk(): ChunkArguments
    {
        return new ChunkArguments; //no arguments
    }
}
