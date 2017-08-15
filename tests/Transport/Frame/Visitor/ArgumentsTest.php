<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\Transport\Frame\{
    Visitor\Arguments,
    Value,
    Value\Bits,
    Value\LongString
};
use Innmind\Immutable\{
    StreamInterface,
    Str
};
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
    public function testInvokation()
    {
        $visit = new Arguments(
            Bits::class,
            LongString::class
        );

        $arguments = new Bits(true).new LongString(new Str('foo'));

        $stream = $visit(new Str($arguments));

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame(Value::class, (string) $stream->type());
        $this->assertCount(2, $stream);
        $this->assertInstanceOf(Bits::class, $stream->get(0));
        $this->assertInstanceOf(LongString::class, $stream->get(1));
        $this->assertSame([true], $stream->get(0)->original()->toPrimitive());
        $this->assertSame('foo', (string) $stream->get(1)->original());
    }
}
