<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\Arguments,
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
                $visit = $this->qosOk();
                break;

            case Methods::get('basic.consume-ok')->equals($method):
                $visit = $this->consumeOk();
                break;

            case Methods::get('basic.cancel-ok')->equals($method):
                $visit = $this->cancelOk();
                break;

            case Methods::get('basic.return')->equals($method):
                $visit = $this->return();
                break;

            case Methods::get('basic.deliver')->equals($method):
                $visit = $this->deliver();
                break;

            case Methods::get('basic.get-ok')->equals($method):
                $visit = $this->getOk();
                break;

            case Methods::get('basic.get-empty')->equals($method):
                $visit = $this->getEmpty();
                break;

            case Methods::get('basic.recover-ok')->equals($method):
                $visit = $this->recoverOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $visit($arguments);
    }

    private function qosOk(): Arguments
    {
        return new Arguments; //no arguments
    }

    private function consumeOk(): Arguments
    {
        return new Arguments(
            ShortString::class //consumer tag
        );
    }

    private function cancelOk(): Arguments
    {
        return new Arguments(
            ShortString::class //consumer tag
        );
    }

    private function return(): Arguments
    {
        return new Arguments(
            UnsignedShortInteger::class, //reply code
            ShortString::class, //reply text
            ShortString::class, //exchange
            ShortString::class //routing key
        );
    }

    private function deliver(): Arguments
    {
        return new Arguments(
            ShortString::class, //consumer tag
            UnsignedLongLongInteger::class, //delivery tag
            Bits::class, //redelivered
            ShortString::class, //exchange
            ShortString::class //routing key
        );
    }

    private function getOk(): Arguments
    {
        return new Arguments(
            UnsignedLongLongInteger::class, //delivery tag
            Bits::class, //redelivered
            ShortString::class, //exchange
            ShortString::class, //routing key
            UnsignedLongInteger::class //message count
        );
    }

    private function getEmpty(): Arguments
    {
        return new Arguments; //no arguments
    }

    private function recoverOk(): Arguments
    {
        return new Arguments; //no arguments
    }
}
