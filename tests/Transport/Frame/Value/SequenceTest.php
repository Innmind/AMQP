<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\Sequence,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value\Text,
    Transport\Frame\Value,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Sequence as Seq,
    Str,
};
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, Sequence::of());
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $values)
    {
        $value = Sequence::of(...$values);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertInstanceOf(Seq::class, $value->original());
        $this->assertSame($values, $value->original()->toList());
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
                $value->original()->get($i)->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $this->assertSame(
                $v->pack()->toString(),
                $value->original()->get($i)->match(
                    static fn($value) => $value->pack()->toString(),
                    static fn() => null,
                ),
            );
        }

        $this->assertSame($string, $value->pack()->toString());
    }

    public function cases(): array
    {
        return [
            [
                \pack('N', 8).'S'.\pack('N', 3).'foo',
                [LongString::literal('foo')],
            ],
            [
                \pack('N', 20).'S'.\pack('N', 3).'fooS'.\pack('N', 7).'🙏bar',
                [
                    LongString::literal('foo'),
                    LongString::literal('🙏bar'),
                ],
            ],
        ];
    }
}
