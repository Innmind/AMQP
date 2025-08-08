<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\Close;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class CloseTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $command = Close::demand();

        $this->assertNull($command->response()->match(
            static fn($info) => $info,
            static fn() => null,
        ));
    }

    #[Group('ci')]
    #[Group('local')]
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
