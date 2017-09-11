<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Sequence,
    Value\LongString,
    Value\Text,
    Value
};
use Innmind\Immutable\{
    Str,
    StreamInterface
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
    public function testFromString($string, $expected)
    {
        $value = Sequence::fromString(new Str($string));

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

    /**
     * @dataProvider cases
     */
    public function testCut($string)
    {
        $str = Sequence::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\UnboundedTextCannotBeWrapped
     */
    public function testThrowWhenUsingUnboundedText()
    {
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
