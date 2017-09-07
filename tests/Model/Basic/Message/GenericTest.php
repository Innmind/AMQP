<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\{
    Message\Generic,
    Message
};
use Innmind\Immutable\{
    Map,
    Str
};
use PHPUnit\Framework\TestCase;

class GenericTest extends TestCase
{
    public function testInterface()
    {
        $message = new Generic(
            $properties = new Map('string', 'mixed'),
            $body = new Str('')
        );

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($properties, $message->properties());
        $this->assertSame($body, $message->body());
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type MapInterface<string, mixed>
     */
    public function testThrowWhenInvalidProperties()
    {
        new Generic(new Map('string', 'string'), new Str(''));
    }
}
