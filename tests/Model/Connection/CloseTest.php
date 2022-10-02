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

        $this->assertNull($command->response()->match(
            static fn($info) => $info,
            static fn() => null,
        ));
        $this->assertNull($command->cause()->match(
            static fn($cause) => $cause,
            static fn() => null,
        ));
    }

    public function testReply()
    {
        $command = Close::reply(42, 'foo');

        $this->assertInstanceOf(Close::class, $command);
        $this->assertSame([42, 'foo'], $command->response()->match(
            static fn($info) => $info,
            static fn() => null,
        ));
        $this->assertNull($command->cause()->match(
            static fn($cause) => $cause,
            static fn() => null,
        ));
    }

    public function testCausedBy()
    {
        $command = new Close;
        $command2 = $command->causedBy('connection.open');

        $this->assertInstanceOf(Close::class, $command2);
        $this->assertSame('connection.open', $command2->cause()->match(
            static fn($cause) => $cause,
            static fn() => null,
        ));
    }
}
