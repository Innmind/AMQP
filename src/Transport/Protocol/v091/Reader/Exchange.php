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

final class Exchange
{
    /**
     * @return StreamInterface<Value>
     */
    public function __invoke(Method $method, Str $arguments): StreamInterface
    {
        switch (true) {
            case Methods::get('exchange.declare-ok')->equals($method):
                $visit = $this->declareOk();
                break;

            case Methods::get('exchange.delete-ok')->equals($method):
                $visit = $this->deleteOk();
                break;

            default:
                throw new UnknownMethod($method);
        }

        return $visit($arguments);
    }

    private function declareOk(): Arguments
    {
        return new Arguments; //no arguments
    }

    private function deleteOk(): Arguments
    {
        return new Arguments; //no arguments
    }
}
