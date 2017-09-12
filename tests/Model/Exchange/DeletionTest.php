<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Exchange;

use Innmind\AMQP\Model\Exchange\Deletion;
use PHPUnit\Framework\TestCase;

class DeletionTest extends TestCase
{
    public function testInterface()
    {
        $command = new Deletion('foo');

        $this->assertSame('foo', $command->name());
        $this->assertFalse($command->onlyIfUnused());
        $this->assertTrue($command->shouldWait());
    }

    public function testIfUnused()
    {
        $command = new Deletion('foo');
        $command2 = $command->ifUnused();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->onlyIfUnused());
        $this->assertTrue($command2->onlyIfUnused());
    }

    public function testIfUsed()
    {
        $command = new Deletion('foo');
        $command2 = $command->ifUsed();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->onlyIfUnused());
        $this->assertFalse($command2->onlyIfUnused());
    }

    public function testDontWait()
    {
        $command = new Deletion('foo');
        $command2 = $command->dontWait();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertFalse($command2->shouldWait());
    }

    public function testWait()
    {
        $command = new Deletion('foo');
        $command2 = $command->wait();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertTrue($command2->shouldWait());
    }
}
