<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\VoidValue,
    Value,
};
use Innmind\Filesystem\Stream\StringStream;
use PHPUnit\Framework\TestCase;

class VoidValueTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new VoidValue);
        $this->assertInstanceOf(VoidValue::class, VoidValue::fromStream(new StringStream('')));
        $this->assertSame('', (string) new VoidValue);
    }
}
