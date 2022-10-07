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
        /** @psalm-suppress UnhandledMatchCondition todo regroup everything in the Reader class */
        $chunk = match ($method) {
            Method::queueDeclareOk => $this->declareOk(),
            Method::queueBindOk => $this->bindOk(),
            Method::queueUnbindOk => $this->unbindOk(),
            Method::queuePurgeOk => $this->purgeOk(),
            Method::queueDeleteOk => $this->deleteOk(),
        };

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
