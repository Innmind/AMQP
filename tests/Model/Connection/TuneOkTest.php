<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\{
    TuneOk,
    MaxChannels,
    MaxFrameSize,
};
use Innmind\TimeContinuum\ElapsedPeriod;
use PHPUnit\Framework\TestCase;

class TuneOkTest extends TestCase
{
    public function testInterface()
    {
        $command = new TuneOk(
            new MaxChannels(1),
            new MaxFrameSize(10),
            $heartbeat = new ElapsedPeriod(1000)
        );

        $this->assertSame(1, $command->maxChannels());
        $this->assertSame(10, $command->maxFrameSize());
        $this->assertSame($heartbeat, $command->heartbeat());
    }
}
