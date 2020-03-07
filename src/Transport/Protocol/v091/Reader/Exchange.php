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

final class Exchange
{
    /**
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
    {
        switch (true) {
            case Methods::get('exchange.declare-ok')->equals($method):
                $chunk = $this->declareOk();
                break;

            case Methods::get('exchange.delete-ok')->equals($method):
                $chunk = $this->deleteOk();
                break;

            default:
                throw new UnknownMethod($method);
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
