<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\VoidValue,
    Value,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class VoidValueTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new VoidValue);
        $this->assertSame('', (new VoidValue)->pack()->toString());
    }
}
