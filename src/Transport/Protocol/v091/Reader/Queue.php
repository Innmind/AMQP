<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\Arguments,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Protocol\v091\Methods,
    Exception\UnknownMethod
};
use Innmind\Immutable\{
    Str,
    StreamInterface
};

final class Queue
{
    /**
     * @return StreamInterface<Value>
     */
    public function __invoke(Method $method, Str $arguments): StreamInterface
    {
        switch (true) {
            case Methods::get('queue.declare-ok')->equals($method):
                $visit = $this->declareOk();
                break;

            case Methods::get('queue.bind-ok')->equals($method):
                $visit = $this->bindOk();
                break;

            case Methods::get('queue.unbind-ok')->equals($method):
                $visit = $this->unbindOk();
                break;

            case Methods::get('queue.purge-ok')->equals($method):
                $visit = $this->purgeOk();
                break;

            case Methods::get('queue.delete-ok')->equals($method):
                $visit = $this->deleteOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $visit($arguments);
    }

    private function declareOk(): Arguments
    {
        return new Arguments(
            ShortString::class, //queue
            UnsignedLongInteger::class, //message count
            UnsignedLongInteger::class //consumer count
        );
    }

    private function bindOk(): Arguments
    {
        return new Arguments; //no arguments
    }

    private function unbindOk(): Arguments
    {
        return new Arguments; //no arguments
    }

    private function purgeOk(): Arguments
    {
        return new Arguments(
            UnsignedLongInteger::class //message count
        );
    }

    private function deleteOk(): Arguments
    {
        return new Arguments(
            UnsignedLongInteger::class //message count
        );
    }
}
