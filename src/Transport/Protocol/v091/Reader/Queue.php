<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\ChunkArguments,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Protocol\v091\Methods,
    Exception\UnknownMethod,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class Queue
{
    /**
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
    {
        switch (true) {
            case Methods::get('queue.declare-ok')->equals($method):
                $chunk = $this->declareOk();
                break;

            case Methods::get('queue.bind-ok')->equals($method):
                $chunk = $this->bindOk();
                break;

            case Methods::get('queue.unbind-ok')->equals($method):
                $chunk = $this->unbindOk();
                break;

            case Methods::get('queue.purge-ok')->equals($method):
                $chunk = $this->purgeOk();
                break;

            case Methods::get('queue.delete-ok')->equals($method):
                $chunk = $this->deleteOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $chunk($arguments);
    }

    private function declareOk(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class, //queue
            UnsignedLongInteger::class, //message count
            UnsignedLongInteger::class //consumer count
        );
    }

    private function bindOk(): ChunkArguments
    {
        return new ChunkArguments; //no arguments
    }

    private function unbindOk(): ChunkArguments
    {
        return new ChunkArguments; //no arguments
    }

    private function purgeOk(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedLongInteger::class //message count
        );
    }

    private function deleteOk(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedLongInteger::class //message count
        );
    }
}
