<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Queue;

use Innmind\AMQP\{
    Model\Queue\Declaration,
    Exception\ExclusivePassiveDeclarationNotAllowed,
    Exception\NotWaitingPassiveDeclarationDoesNothing,
    Exception\PassiveQueueDeclarationMustHaveAName,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class DeclarationTest extends TestCase
{
    public function testPassive()
    {
        $command = Declaration::passive('foo');

        $this->assertInstanceOf(Declaration::class, $command);
        $this->assertFalse($command->shouldAutoGenerateName());
        $this->assertTrue($command->isPassive());
        $this->assertFalse($command->isDurable());
        $this->assertFalse($command->isAutoDeleted());
        $this->assertFalse($command->isExclusive());
        $this->assertTrue($command->shouldWait());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testDurable()
    {
        $command = Declaration::durable();

        $this->assertInstanceOf(Declaration::class, $command);
        $this->assertTrue($command->shouldAutoGenerateName());
        $this->assertFalse($command->isPassive());
        $this->assertTrue($command->isDurable());
        $this->assertFalse($command->isAutoDeleted());
        $this->assertFalse($command->isExclusive());
        $this->assertTrue($command->shouldWait());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testTemporary()
    {
        $command = Declaration::temporary();

        $this->assertInstanceOf(Declaration::class, $command);
        $this->assertTrue($command->shouldAutoGenerateName());
        $this->assertFalse($command->isPassive());
        $this->assertFalse($command->isDurable());
        $this->assertFalse($command->isAutoDeleted());
        $this->assertFalse($command->isExclusive());
        $this->assertTrue($command->shouldWait());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testAutoDelete()
    {
        $command = Declaration::autoDelete();

        $this->assertInstanceOf(Declaration::class, $command);
        $this->assertTrue($command->shouldAutoGenerateName());
        $this->assertFalse($command->isPassive());
        $this->assertFalse($command->isDurable());
        $this->assertTrue($command->isAutoDeleted());
        $this->assertFalse($command->isExclusive());
        $this->assertTrue($command->shouldWait());
        $this->assertInstanceOf(Map::class, $command->arguments());
        $this->assertCount(0, $command->arguments());
    }

    public function testExclusive()
    {
        $command = Declaration::durable();
        $command2 = $command->exclusive();

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->isExclusive());
        $this->assertTrue($command2->isExclusive());
    }

    public function testThrowWhenUsingExclusivePassiveDeclaration()
    {
        $this->expectException(ExclusivePassiveDeclarationNotAllowed::class);

        Declaration::passive('foo')->exclusive();
    }

    public function testNotExclusive()
    {
        $command = Declaration::durable();
        $command2 = $command->notExclusive();

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->isExclusive());
        $this->assertFalse($command2->isExclusive());
    }

    public function testDontWait()
    {
        $command = Declaration::durable();
        $command2 = $command->dontWait();

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertFalse($command2->shouldWait());
    }

    public function testThrowWhenNotWaitingPassiveDeclaration()
    {
        $this->expectException(NotWaitingPassiveDeclarationDoesNothing::class);

        Declaration::passive('foo')->dontWait();
    }

    public function testWait()
    {
        $command = Declaration::durable();
        $command2 = $command->wait();

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldWait());
        $this->assertTrue($command2->shouldWait());
    }

    public function testWithName()
    {
        $command = Declaration::durable();
        $command2 = $command->withName('foo');

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertTrue($command->shouldAutoGenerateName());
        $this->assertFalse($command2->shouldAutoGenerateName());
        $this->assertSame('foo', $command2->name());
    }

    public function testWithAutoGeneratedName()
    {
        $command = Declaration::durable()->withName('foo');
        $command2 = $command->withAutoGeneratedName();

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->shouldAutoGenerateName());
        $this->assertTrue($command2->shouldAutoGenerateName());
    }

    public function testThrowWhenAskingForAutoGeneratedNameOnPassiveDeclaration()
    {
        $this->expectException(PassiveQueueDeclarationMustHaveAName::class);

        Declaration::passive('foo')->withAutoGeneratedName();
    }

    public function testWithArgument()
    {
        $command = Declaration::durable();
        $command2 = $command->withArgument('foo', [42]);

        $this->assertInstanceOf(Declaration::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertCount(0, $command->arguments());
        $this->assertCount(1, $command2->arguments());
        $this->assertSame([42], $command2->arguments()->get('foo')->match(
            static fn($argument) => $argument,
            static fn() => null,
        ));
    }
}
