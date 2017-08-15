<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091\Reader;

use Innmind\AMQP\{
    Transport\Frame\Method,
    Transport\Frame\Visitor\Arguments,
    Transport\Protocol\v091\Methods,
    Exception\UnknownMethod
};
use Innmind\Immutable\{
    Str,
    StreamInterface
};

final class Transaction
{
    /**
     * @return StreamInterface<Value>
     */
    public function __invoke(Method $method, Str $arguments): StreamInterface
    {
        switch (true) {
            case Methods::get('tx.select-ok')->equals($method):
                $visit = $this->selectOk();
                break;

            case Methods::get('tx.commit-ok')->equals($method):
                $visit = $this->commitOk();
                break;

            case Methods::get('tx.rollback-ok')->equals($method):
                $visit = $this->rollbackOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $visit($arguments);
    }

    private function selectOk(): Arguments
    {
        return new Arguments; //no arguments
    }

    private function commitOk(): Arguments
    {
        return new Arguments; //no arguments
    }

    private function rollbackOk(): Arguments
    {
        return new Arguments; //no arguments
    }
}
