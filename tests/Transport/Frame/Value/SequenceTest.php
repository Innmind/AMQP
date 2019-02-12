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
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    StreamInterface,
    Str,
};
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
        $this->assertSame($expected, (string) $value);
        $this->assertInstanceOf(StreamInterface::class, $value->original());
        $this->assertSame(Value::class, (string) $value->original()->type());
        $this->assertSame($values, $value->original()->toPrimitive());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($string, $expected)
    {
        $value = Sequence::fromStream(new StringStream($string));

        $this->assertInstanceOf(Sequence::class, $value);
        $this->assertCount(count($expected), $value->original());

        foreach ($expected as $i => $v) {
            $this->assertInstanceOf(
                get_class($v),
                $value->original()->get($i)
            );
            $this->assertSame(
                (string) $v,
                (string) $value->original()->get($i)
            );
        }

        $this->assertSame($string, (string) $value);
    }

    public function testThrowWhenUsingUnboundedText()
    {
        $this->expectException(UnboundedTextCannotBeWrapped::class);

        new Sequence(new Text(new Str('')));
    }

    public function cases(): array
    {
        return [
            [
                pack('N', 8).'S'.pack('N', 3).'foo',
                [new LongString(new Str('foo'))]
            ],
            [
                pack('N', 20).'S'.pack('N', 3).'fooS'.pack('N', 7).'🙏bar',
                [
                    new LongString(new Str('foo')),
                    new LongString(new Str('🙏bar')),
                ],
            ],
        ];
    }
}
