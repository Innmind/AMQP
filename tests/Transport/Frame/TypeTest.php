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
        $type = Type::$type();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame($expected, $type->toInt());
    }

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
