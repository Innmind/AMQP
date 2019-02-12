<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame;

use Innmind\AMQP\{
    Transport\Frame\Channel,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame(0, (new Channel(0))->toInt());
        $this->assertSame(42, (new Channel(42))->toInt());
    }

    public function testThrowWhenChannelIdTooLow()
    {
        $this->expectException(DomainException::class);

        new Channel(-1);
    }
}
