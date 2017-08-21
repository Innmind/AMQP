<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\Transport\{
    Protocol\v091\Protocol,
    Protocol\v091\Connection,
    Protocol\v091\Channel,
    Protocol\v091\Exchange,
    Protocol\v091\Queue,
    Protocol\v091\Basic,
    Protocol\v091\Transaction,
    Protocol as ProtocolInterface,
    Protocol\Version
};
use PHPUnit\Framework\TestCase;

class ProtocolTest extends TestCase
{
    public function testInterface()
    {
        $protocol = new Protocol;

        $this->assertInstanceOf(ProtocolInterface::class, $protocol);
        $this->assertInstanceOf(Version::class, $protocol->version());
        $this->assertSame("AMQP\x00\x00\x09\x01", (string) $protocol->version());
        $this->assertInstanceOf(Connection::class, $protocol->connection());
        $this->assertInstanceOf(Channel::class, $protocol->channel());
        $this->assertInstanceOf(Exchange::class, $protocol->exchange());
        $this->assertInstanceOf(Queue::class, $protocol->queue());
        $this->assertInstanceOf(Basic::class, $protocol->basic());
        $this->assertInstanceOf(Transaction::class, $protocol->transaction());
    }
}
