<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\Cancel;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class CancelTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $command = Cancel::of('consumer');

        $this->assertSame('consumer', $command->consumerTag());
        $this->assertTrue($command->shouldWait());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testWait()
    {
        $command = Cancel::of('consumer');
        $command2 = $command->dontWait();

        $this->assertInstanceOf(Cancel::class, $command2);
        $this->assertNotSame($command, $command2);
        $this->assertSame('consumer', $command->consumerTag());
        $this->assertSame('consumer', $command2->consumerTag());
        $this->assertTrue($command->shouldWait());
        $this->assertFalse($command2->shouldWait());

        $command3 = $command2->wait();

        $this->assertInstanceOf(Cancel::class, $command3);
        $this->assertNotSame($command3, $command2);
        $this->assertSame('consumer', $command2->consumerTag());
        $this->assertSame('consumer', $command3->consumerTag());
        $this->assertFalse($command2->shouldWait());
        $this->assertTrue($command3->shouldWait());
    }
}
