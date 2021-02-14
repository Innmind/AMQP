<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\Sequence,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\Text,
    Transport\Frame\Value,
    Exception\UnboundedTextCannotBeWrapped,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Sequence as Seq,
    Str,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new Sequence);
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $values)
    {
        $value = new Sequence(...$values);
        $this->assertSame($expected, $value->pack());
        $this->assertInstanceOf(Seq::class, $value->original());
        $this->assertSame(Value::class, (string) $value->original()->type());
        $this->assertSame($values, unwrap($value->original()));
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($string, $expected)
    {
        $value = Sequence::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(Sequence::class, $value);
        $this->assertCount(\count($expected), $value->original());

        foreach ($expected as $i => $v) {
            $this->assertInstanceOf(
                \get_class($v),
                $value->original()->get($i)
            );
            $this->assertSame(
                $v->pack(),
                $value->original()->get($i)->pack()
            );
        }

        $this->assertSame($string, $value->pack());
    }

    public function testThrowWhenUsingUnboundedText()
    {
        $this->expectException(UnboundedTextCannotBeWrapped::class);

        new Sequence(new Text(Str::of('')));
    }

    public function cases(): array
    {
        return [
            [
                \pack('N', 8).'S'.\pack('N', 3).'foo',
                [new LongString(Str::of('foo'))],
            ],
            [
                \pack('N', 20).'S'.\pack('N', 3).'fooS'.\pack('N', 7).'🙏bar',
                [
                    new LongString(Str::of('foo')),
                    new LongString(Str::of('🙏bar')),
                ],
            ],
        ];
    }
}
