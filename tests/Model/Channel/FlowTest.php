<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Channel;

use Innmind\AMQP\Model\Channel\Flow;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class FlowTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testStart()
    {
        $command = Flow::start;

        $this->assertTrue($command->active());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testStop()
    {
        $command = Flow::stop;

        $this->assertFalse($command->active());
    }
}
