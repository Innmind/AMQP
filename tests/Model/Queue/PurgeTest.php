<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\Queue\Purge;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class PurgeTest extends TestCase
{
    public function testInterface()
    {
        $command = Purge::of('foo');

        $this->assertSame('foo', $command->name());
        $this->assertTrue($command->shouldWait());
    }

    public function testDontWait()
    {
        $command = Purge::of('foo');
        $command2 = $command->dontWait();

        $this->assertInstanceOf(Purge::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertFalse($command2->shouldWait());
    }

    public function testWait()
    {
        $command = Purge::of('foo');
        $command2 = $command->wait();

        $this->assertInstanceOf(Purge::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertTrue($command2->shouldWait());
    }
}
