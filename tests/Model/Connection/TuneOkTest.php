<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Connection;

use Innmind\AMQP\Model\Connection\TuneOk;
use Innmind\TimeContinuum\ElapsedPeriod;
use PHPUnit\Framework\TestCase;

class TuneOkTest extends TestCase
{
    public function testInterface()
    {
        $command = new TuneOk(
            1,
            2,
            $heartbeat = new ElapsedPeriod(1000)
        );

        $this->assertSame(1, $command->maxChannels());
        $this->assertSame(2, $command->maxFrameSize());
        $this->assertSame($heartbeat, $command->heartbeat());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\DomainException
     */
    public function testThrowWhenNegativeChannels()
    {
        new TuneOk(-1, 0, new ElapsedPeriod(1));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\DomainException
     */
    public function testThrowWhenNegativeFrameSize()
    {
        new TuneOk(0, -1, new ElapsedPeriod(1));
    }
}
