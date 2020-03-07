<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Visitor\ChunkArguments,
    Transport\Protocol\v091\Methods,
    Exception\UnknownMethod,
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
            case Methods::get('tx.select-ok')->equals($method):
                $chunk = $this->selectOk();
                break;

            case Methods::get('tx.commit-ok')->equals($method):
                $chunk = $this->commitOk();
                break;

            case Methods::get('tx.rollback-ok')->equals($method):
                $chunk = $this->rollbackOk();
                break;

            default:
                throw new UnknownMethod($method);
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
