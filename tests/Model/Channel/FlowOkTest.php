<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Channel;

use Innmind\AMQP\Model\Channel\FlowOk;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class FlowOkTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $command = FlowOk::of(true);

        $this->assertTrue($command->active());

        $command = FlowOk::of(false);

        $this->assertFalse($command->active());
    }
}
