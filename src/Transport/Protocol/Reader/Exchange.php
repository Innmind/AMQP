<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Visitor\ChunkArguments,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class Exchange
{
    /**
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
    {
        /** @psalm-suppress UnhandledMatchCondition todo regroup everything in the Reader class */
        $chunk = match ($method) {
            Method::exchangeDeclareOk => $this->declareOk(),
            Method::exchangeDeleteOk => $this->deleteOk(),
        };

        return $chunk($arguments);
    }

    private function declareOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function deleteOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }
}
