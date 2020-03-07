<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Visitor;

use Innmind\AMQP\Transport\Frame\{
    Visitor\ChunkArguments,
    Value,
    Value\Bits,
    Value\LongString,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Sequence,
    Str,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class ChunkArgumentsTest extends TestCase
{
    public function testInvokation()
    {
        $visit = new ChunkArguments(
            Bits::class,
            LongString::class
        );

        $arguments = (new Bits(true))->pack().(new LongString(Str::of('foo')))->pack();

        $stream = $visit(Stream::ofContent($arguments));

        $this->assertInstanceOf(Sequence::class, $stream);
        $this->assertSame(Value::class, (string) $stream->type());
        $this->assertCount(2, $stream);
        $this->assertInstanceOf(Bits::class, $stream->get(0));
        $this->assertInstanceOf(LongString::class, $stream->get(1));
        $this->assertSame([true], unwrap($stream->get(0)->original()));
        $this->assertSame('foo', $stream->get(1)->original()->toString());
    }
}
