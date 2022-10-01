<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Exchange;

use Innmind\AMQP\{
    Client\Channel\Exchange\NullExchange,
    Client\Channel\Exchange,
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
    Model\Exchange\Type,
};
use PHPUnit\Framework\TestCase;

class NullExchangeTest extends TestCase
{
    public function testInterface()
    {
        $exchange = new NullExchange;

        $this->assertInstanceOf(Exchange::class, $exchange);
        $this->assertNull($exchange->declare(Declaration::passive('', Type::direct)));
        $this->assertNull($exchange->delete(new Deletion('')));
    }
}
