<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\Recover;
use PHPUnit\Framework\TestCase;

class RecoverTest extends TestCase
{
    public function testInterface()
    {
        $command = Recover::withoutRequeue();

        $this->assertFalse($command->shouldRequeue());
    }

    public function testRequeue()
    {
        $command = Recover::requeue();

        $this->assertInstanceOf(Recover::class, $command);
        $this->assertTrue($command->shouldRequeue());
    }
}
