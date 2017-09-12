<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\Close;
use PHPUnit\Framework\TestCase;

class CloseTest extends TestCase
{
    public function testInterface()
    {
        $command = new Close;

        $this->assertFalse($command->hasReply());
        $this->assertFalse($command->causedKnown());
    }

    public function testReply()
    {
        $command = Close::reply(42, 'foo');

        $this->assertInstanceOf(Close::class, $command);
        $this->assertTrue($command->hasReply());
        $this->assertSame(42, $command->replyCode());
        $this->assertSame('foo', $command->replyText());
        $this->assertFalse($command->causedKnown());
    }

    public function testCausedBy()
    {
        $command = new Close;
        $command2 = $command->causedBy('connection.open');

        $this->assertInstanceOf(Close::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->causedKnown());
        $this->assertTrue($command2->causedKnown());
        $this->assertSame('connection.open', $command2->cause());
    }
}
