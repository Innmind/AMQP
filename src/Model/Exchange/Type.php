<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Exchange;

/**
 * @psalm-immutable
 */
enum Type
{
    case direct;
    case fanout;
    case topic;
    case headers;

    public function toString(): string
    {
        return $this->name;
    }
}
