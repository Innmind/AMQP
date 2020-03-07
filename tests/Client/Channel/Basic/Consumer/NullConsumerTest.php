<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel\Basic\Consumer;

use Innmind\AMQP\Client\Channel\Basic\{
    Consumer\NullConsumer,
    Consumer,
};
use PHPUnit\Framework\TestCase;

class NullConsumerTest extends TestCase
{
    public function testInterface()
    {
        $consumer = new NullConsumer;

        $this->assertInstanceOf(Consumer::class, $consumer);
        $this->assertNull($consumer->take(42));
        $this->assertNull($consumer->filter(function(){}));
        $this->assertNull($consumer->foreach(function(){}));
    }
}
