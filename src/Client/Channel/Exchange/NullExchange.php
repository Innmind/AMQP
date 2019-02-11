<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel\Exchange;

use Innmind\AMQP\{
    Client\Channel\Exchange as ExchangeInterface,
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
};

final class NullExchange implements ExchangeInterface
{
    public function declare(Declaration $command): ExchangeInterface
    {
        return $this;
    }

    public function delete(Deletion $command): ExchangeInterface
    {
        return $this;
    }
}
