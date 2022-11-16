<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Queue;

use Innmind\AMQP\Model\Queue\Unbinding;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class UnbindingTest extends TestCase
{
    public function testInterface()
    {
        $command = Unbinding::of('foo', 'bar', 'baz');

        $this->assertSame('foo', $command->exchange());
        $this->assertSame('bar', $command->queue());
        $this->assertSame('baz', $command->routingKey());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testWithArgument()
    {
        $command = Unbinding::of('foo', 'bar', 'baz');
        $command2 = $command->withArgument('f', [42]);

        $this->assertInstanceOf(Unbinding::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertCount(0, $command->arguments());
        $this->assertCount(1, $command2->arguments());
        $this->assertSame([42], $command2->arguments()->get('f')->match(
            static fn($argument) => $argument,
            static fn() => null,
        ));
    }
}
