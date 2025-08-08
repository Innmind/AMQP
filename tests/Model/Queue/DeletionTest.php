<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\Queue\Deletion;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class DeletionTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $command = Deletion::of('foo');

        $this->assertSame('foo', $command->name());
        $this->assertFalse($command->onlyIfUnused());
        $this->assertFalse($command->onlyIfEmpty());
        $this->assertTrue($command->shouldWait());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testIfUnused()
    {
        $command = Deletion::of('foo');
        $command2 = $command->ifUnused();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->onlyIfUnused());
        $this->assertTrue($command2->onlyIfUnused());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testIfUsed()
    {
        $command = Deletion::of('foo');
        $command2 = $command->ifUsed();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->onlyIfUnused());
        $this->assertFalse($command2->onlyIfUnused());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testIfEmpty()
    {
        $command = Deletion::of('foo');
        $command2 = $command->ifEmpty();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->onlyIfEmpty());
        $this->assertTrue($command2->onlyIfEmpty());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testIfNotEmpty()
    {
        $command = Deletion::of('foo');
        $command2 = $command->ifNotEmpty();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->onlyIfEmpty());
        $this->assertFalse($command2->onlyIfEmpty());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testDontWait()
    {
        $command = Deletion::of('foo');
        $command2 = $command->dontWait();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertFalse($command2->shouldWait());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testWait()
    {
        $command = Deletion::of('foo');
        $command2 = $command->wait();

        $this->assertInstanceOf(Deletion::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertTrue($command2->shouldWait());
    }
}
