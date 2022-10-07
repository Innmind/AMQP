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
        /** @psalm-suppress UnhandledMatchCondition todo regroup everything in the Reader class */
        $chunk = match ($method) {
            Method::transactionSelectOk => $this->selectOk(),
            Method::transactionCommitOk => $this->commitOk(),
            Method::transactionRollbackOk => $this->rollbackOk(),
        };

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
