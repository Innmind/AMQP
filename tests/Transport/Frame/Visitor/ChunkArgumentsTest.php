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
use PHPUnit\Framework\TestCase;

class ChunkArgumentsTest extends TestCase
{
    public function testInvokation()
    {
        $visit = new ChunkArguments(
            Bits::class,
            LongString::class,
        );

        $arguments = Bits::of(true)->pack().LongString::literal('foo')->pack();

        $stream = $visit(Stream::ofContent($arguments));

        $this->assertInstanceOf(Sequence::class, $stream);
        $this->assertCount(2, $stream);
        $this->assertInstanceOf(Bits::class, $stream->get(0)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertInstanceOf(LongString::class, $stream->get(1)->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame([true], $stream->get(0)->match(
            static fn($value) => $value->original()->toList(),
            static fn() => null,
        ));
        $this->assertSame('foo', $stream->get(1)->match(
            static fn($value) => $value->original()->toString(),
            static fn() => null,
        ));
    }
}
