<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Exchange;

use Innmind\AMQP\{
    Model\Exchange\Declaration,
    Model\Exchange\Type,
    Exception\NotWaitingPassiveDeclarationDoesNothing,
};
use Innmind\Immutable\Map;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class DeclarationTest extends TestCase
{
    public function testPassive()
    {
        $command = Declaration::passive('foo', $type = Type::direct);

        $this->assertInstanceOf(Declaration::class, $command);
        $this->assertSame('foo', $command->name());
        $this->assertSame($type, $command->type());
        $this->assertTrue($command->isPassive());
        $this->assertFalse($command->isDurable());
        $this->assertFalse($command->isAutoDeleted());
        $this->assertTrue($command->shouldWait());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testDurable()
    {
        $command = Declaration::durable('foo', $type = Type::direct);

        $this->assertInstanceOf(Declaration::class, $command);
        $this->assertSame('foo', $command->name());
        $this->assertSame($type, $command->type());
        $this->assertFalse($command->isPassive());
        $this->assertTrue($command->isDurable());
        $this->assertFalse($command->isAutoDeleted());
        $this->assertTrue($command->shouldWait());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testTemporary()
    {
        $command = Declaration::temporary('foo', $type = Type::direct);

        $this->assertInstanceOf(Declaration::class, $command);
        $this->assertSame('foo', $command->name());
        $this->assertSame($type, $command->type());
        $this->assertFalse($command->isPassive());
        $this->assertFalse($command->isDurable());
        $this->assertFalse($command->isAutoDeleted());
        $this->assertTrue($command->shouldWait());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testAutoDeleted()
    {
        $command = Declaration::autoDelete('foo', $type = Type::direct);

        $this->assertInstanceOf(Declaration::class, $command);
        $this->assertSame('foo', $command->name());
        $this->assertSame($type, $command->type());
        $this->assertFalse($command->isPassive());
        $this->assertFalse($command->isDurable());
        $this->assertTrue($command->isAutoDeleted());
        $this->assertTrue($command->shouldWait());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testDontWait()
    {
        $command = Declaration::durable('too', Type::direct);
        $command2 = $command->dontWait();

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertFalse($command2->shouldWait());
    }

    public function testThrowWhenNotWaitingPassiveDeclaration()
    {
        $this->expectException(NotWaitingPassiveDeclarationDoesNothing::class);

        Declaration::passive('foo', Type::direct)->dontWait();
    }

    public function testWait()
    {
        $command = Declaration::passive('foo', Type::direct);
        $command2 = $command->wait();

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertTrue($command2->shouldWait());
    }

    public function testWithArgument()
    {
        $command = Declaration::durable('foo', Type::direct);
        $command2 = $command->withArgument('bar', [42]);

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertCount(0, $command->arguments());
        $this->assertCount(1, $command2->arguments());
        $this->assertSame([42], $command2->arguments()->get('bar')->match(
            static fn($argument) => $argument,
            static fn() => null,
        ));
    }
}
