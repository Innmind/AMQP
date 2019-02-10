<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\VoidValue,
    Value
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class VoidValueTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new VoidValue);
        $this->assertInstanceOf(VoidValue::class, VoidValue::fromString(new Str('')));
        $this->assertInstanceOf(VoidValue::class, VoidValue::fromStream(new StringStream('')));
        $this->assertSame('', (string) new VoidValue);
        $this->assertInstanceOf(Str::class, VoidValue::cut(new Str('foo')));
        $this->assertSame('', (string) VoidValue::cut(new Str('foo')));
    }
}
