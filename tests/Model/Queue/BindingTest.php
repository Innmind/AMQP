<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\Queue\Binding;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class BindingTest extends TestCase
{
    public function testInterface()
    {
        $command = Binding::of('foo', 'bar', 'baz');

        $this->assertSame('foo', $command->exchange());
        $this->assertSame('bar', $command->queue());
        $this->assertSame('baz', $command->routingKey());
        $this->assertTrue($command->shouldWait());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testDontWait()
    {
        $command = Binding::of('foo', 'bar', 'baz');
        $command2 = $command->dontWait();

        $this->assertInstanceOf(Binding::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertFalse($command2->shouldWait());
    }

    public function testWait()
    {
        $command = Binding::of('foo', 'bar', 'baz');
        $command2 = $command->wait();

        $this->assertInstanceOf(Binding::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertTrue($command2->shouldWait());
    }

    public function testWithArgument()
    {
        $command = Binding::of('foo', 'bar', 'baz');
        $command2 = $command->withArgument('f', [42]);

        $this->assertInstanceOf(Binding::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertCount(0, $command->arguments());
        $this->assertCount(1, $command2->arguments());
        $this->assertSame([42], $command2->arguments()->get('f')->match(
            static fn($argument) => $argument,
            static fn() => null,
        ));
    }
}
