<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Sequence,
    Value\LongString,
    Value
};
use Innmind\Immutable\Str;
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
        $this->assertSame(
            $expected,
            (string) new Sequence(...$values)
        );
    }

    public function cases(): array
    {
        return [
            [
                pack('N', 7).pack('N', 3).'foo',
                [new LongString(new Str('foo'))]
            ],
            [
                pack('N', 18).pack('N', 3).'foo'.pack('N', 7).'🙏bar',
                [
                    new LongString(new Str('foo')),
                    new LongString(new Str('🙏bar')),
                ],
            ],
        ];
    }
}
