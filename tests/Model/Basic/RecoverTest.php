<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\Recover;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class RecoverTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $command = Recover::withoutRequeue();

        $this->assertFalse($command->shouldRequeue());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testRequeue()
    {
        $command = Recover::requeue();

        $this->assertInstanceOf(Recover::class, $command);
        $this->assertTrue($command->shouldRequeue());
    }
}
