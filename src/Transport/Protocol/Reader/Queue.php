<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\ChunkArguments,
    Transport\Frame\Value,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedLongInteger,
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
            case Method::queueDeclareOk->equals($method):
                $chunk = $this->declareOk();
                break;

            case Method::queueBindOk->equals($method):
                $chunk = $this->bindOk();
                break;

            case Method::queueUnbindOk->equals($method):
                $chunk = $this->unbindOk();
                break;

            case Method::queuePurgeOk->equals($method):
                $chunk = $this->purgeOk();
                break;

            case Method::queueDeleteOk->equals($method):
                $chunk = $this->deleteOk();
                break;

            default:
                throw new \RuntimeException;
        }

        return $chunk($arguments);
    }

    private function declareOk(): ChunkArguments
    {
        return new ChunkArguments(
            ShortString::class, // queue
            UnsignedLongInteger::class, // message count
            UnsignedLongInteger::class, // consumer count
        );
    }

    private function bindOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function unbindOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function purgeOk(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedLongInteger::class, // message count
        );
    }

    private function deleteOk(): ChunkArguments
    {
        return new ChunkArguments(
            UnsignedLongInteger::class, // message count
        );
    }
}
