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
            Map::of(
                ['foo', $expected = static function() {}],
            ),
        );

        $this->assertTrue($consumers->contains('foo'));
        $this->assertFalse($consumers->contains('bar'));
        $this->assertSame($expected, $consumers->get('foo'));
    }
}
