<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\{
    TuneOk,
    MaxChannels,
    MaxFrameSize,
};
use Innmind\TimeContinuum\Period;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class TuneOkTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $command = TuneOk::of(
            MaxChannels::of(1),
            MaxFrameSize::of(10),
            $heartbeat = Period::second(1)->asElapsedPeriod(),
        );

        $this->assertSame(1, $command->maxChannels());
        $this->assertSame(10, $command->maxFrameSize());
        $this->assertSame($heartbeat, $command->heartbeat());
    }
}
