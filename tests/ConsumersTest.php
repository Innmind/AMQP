<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP;

use Innmind\AMQP\Consumers;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ConsumersTest extends TestCase
{
    public function testInterface()
    {
        $consumers = new Consumers(
            Map::of('string', 'callable')
                ('foo', $expected = static function() {}),
        );

        $this->assertTrue($consumers->contains('foo'));
        $this->assertFalse($consumers->contains('bar'));
        $this->assertSame($expected, $consumers->get('foo'));
    }

    public function testThrowWhenInvaliMapKey()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, callable>');

        new Consumers(Map::of('int', 'callable'));
    }

    public function testThrowWhenInvaliMapValue()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, callable>');

        new Consumers(Map::of('string', 'string'));
    }
}
