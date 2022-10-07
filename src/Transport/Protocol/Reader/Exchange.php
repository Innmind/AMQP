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
        switch (true) {
            case Method::exchangeDeclareOk->equals($method):
                $chunk = $this->declareOk();
                break;

            case Method::exchangeDeleteOk->equals($method):
                $chunk = $this->deleteOk();
                break;

            default:
                throw new \RuntimeException;
        }

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
