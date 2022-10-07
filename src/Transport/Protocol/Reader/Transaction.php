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

final class Transaction
{
    /**
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
    {
        switch (true) {
            case Method::transactionSelectOk->equals($method):
                $chunk = $this->selectOk();
                break;

            case Method::transactionCommitOk->equals($method):
                $chunk = $this->commitOk();
                break;

            case Method::transactionRollbackOk->equals($method):
                $chunk = $this->rollbackOk();
                break;

            default:
                throw new \RuntimeException;
        }

        return $chunk($arguments);
    }

    private function selectOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function commitOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }

    private function rollbackOk(): ChunkArguments
    {
        return new ChunkArguments; // no arguments
    }
}
