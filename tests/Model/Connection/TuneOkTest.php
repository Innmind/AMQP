<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\{
    TuneOk,
    MaxChannels,
    MaxFrameSize,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use PHPUnit\Framework\TestCase;

class TuneOkTest extends TestCase
{
    public function testInterface()
    {
        $command = TuneOk::of(
            MaxChannels::of(1),
            MaxFrameSize::of(10),
            $heartbeat = ElapsedPeriod::of(1000),
        );

        $this->assertSame(1, $command->maxChannels());
        $this->assertSame(10, $command->maxFrameSize());
        $this->assertSame($heartbeat, $command->heartbeat());
    }
}
