<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame;

use Innmind\AMQP\Transport\Frame\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInterface($expected, $type)
    {
        $instance = Type::$type();

        $this->assertInstanceOf(Type::class, $instance);
        $this->assertSame($instance, Type::$type());
        $this->assertSame($expected, $instance->toInt());
    }

    /**
     * @dataProvider cases
     */
    public function testFromInt($type, $expected)
    {
        $this->assertSame(Type::$expected(), Type::fromInt($type));
    }

    /**
     * @expectedException Innminq\AMQP\Exception\UnknownFrameType
     * @expectedExceptionMessage 4
     */

    public function cases(): array
    {
        return [
            [1, 'method'],
            [2, 'header'],
            [3, 'body'],
            [8, 'heartbeat'],
        ];
    }
}
