<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\{
    Publish,
    Message,
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class PublishTest extends TestCase
{
    public function testInterface()
    {
        $command = Publish::a(
            $message = Message::of(Str::of('')),
        );

        $this->assertSame($message, $command->message());
        $this->assertSame('', $command->exchange());
        $this->assertSame('', $command->routingKey());
        $this->assertFalse($command->mandatory());
        $this->assertFalse($command->immediate());
    }

    public function testA()
    {
        $command = Publish::a($message = Message::of(Str::of('')));

        $this->assertInstanceOf(Publish::class, $command);
        $this->assertSame($message, $command->message());
    }

    public function testTo()
    {
        $command = Publish::a(Message::of(Str::of('')));
        $command2 = $command->to('foo');

        $this->assertInstanceOf(Publish::class, $command2);
        $this->assertNotSame($command, $command2);
        $this->assertSame('', $command->exchange());
        $this->assertSame('foo', $command2->exchange());

        $command3 = $command2->toDefaultExchange();

        $this->assertInstanceOf(Publish::class, $command3);
        $this->assertNotSame($command2, $command3);
        $this->assertSame('foo', $command2->exchange());
        $this->assertSame('', $command3->exchange());
    }

    public function testWithRoutingKey()
    {
        $command = Publish::a(Message::of(Str::of('')));
        $command2 = $command->withRoutingKey('bar');

        $this->assertInstanceOf(Publish::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertSame('', $command->routingKey());
        $this->assertSame('bar', $command2->routingKey());
    }

    public function testFlagAsMandatory()
    {
        $command = Publish::a(Message::of(Str::of('')));
        $command2 = $command->flagAsMandatory();

        $this->assertInstanceOf(Publish::class, $command2);
        $this->assertNotSame($command, $command2);
        $this->assertFalse($command->mandatory());
        $this->assertTrue($command2->mandatory());

        $command3 = $command2->flagAsNotMandatory();

        $this->assertInstanceOf(Publish::class, $command3);
        $this->assertNotSame($command2, $command3);
        $this->assertTrue($command2->mandatory());
        $this->assertFalse($command3->mandatory());
    }

    public function testFlagAsImmediate()
    {
        $command = Publish::a(Message::of(Str::of('')));
        $command2 = $command->flagAsImmediate();

        $this->assertInstanceOf(Publish::class, $command2);
        $this->assertNotSame($command, $command2);
        $this->assertFalse($command->immediate());
        $this->assertTrue($command2->immediate());

        $command3 = $command2->flagAsNotImmediate();

        $this->assertInstanceOf(Publish::class, $command3);
        $this->assertNotSame($command2, $command3);
        $this->assertTrue($command2->immediate());
        $this->assertFalse($command3->immediate());
    }
}
