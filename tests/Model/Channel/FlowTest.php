<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Channel;

use Innmind\AMQP\Model\Channel\Flow;
use PHPUnit\Framework\TestCase;

class FlowTest extends TestCase
{
    public function testStart()
    {
        $command = Flow::start;

        $this->assertTrue($command->active());
    }

    public function testStop()
    {
        $command = Flow::stop;

        $this->assertFalse($command->active());
    }
}
