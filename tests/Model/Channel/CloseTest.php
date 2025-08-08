<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Channel;

use Innmind\AMQP\Model\Channel\Close;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class CloseTest extends TestCase
{
    public function testInterface()
    {
        $command = Close::demand();

        $this->assertNull($command->response()->match(
            static fn($info) => $info,
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
    }
}
