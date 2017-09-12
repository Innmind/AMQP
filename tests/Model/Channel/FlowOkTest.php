<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Channel;

use Innmind\AMQP\Model\Channel\FlowOk;
use PHPUnit\Framework\TestCase;

class FlowOkTest extends TestCase
{
    public function testInterface()
    {
        $command = new FlowOk(true);

        $this->assertTrue($command->active());

        $command = new FlowOk(false);

        $this->assertFalse($command->active());
    }
}
