<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Channel;

use Innmind\AMQP\Model\Channel\FlowOk;
use PHPUnit\Framework\TestCase;

class FlowOkTest extends TestCase
{
    public function testInterface()
    {
        $command = FlowOk::of(true);

        $this->assertTrue($command->active());

        $command = FlowOk::of(false);

        $this->assertFalse($command->active());
    }
}
