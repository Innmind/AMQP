<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedLongInteger,
    Value,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Filesystem\Stream\StringStream;
use PHPUnit\Framework\TestCase;

class UnsignedLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new UnsignedLongInteger(new Integer(0))
        );
    }

    public function testThrowWhenIntegerTooHigh()
    {
        $this->assertSame(
            '[0;4294967295]',
            (string) UnsignedLongInteger::definitionSet()
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $this->assertSame(
            $expected,
            (string) new UnsignedLongInteger(new Integer($int))
        );
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = UnsignedLongInteger::fromStream(new StringStream($string));

        $this->assertInstanceOf(UnsignedLongInteger::class, $value);
        $this->assertInstanceOf(Integer::class, $value->original());
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    public function cases(): array
    {
        return [
            [0, pack('N', 0)],
            [4294967295, pack('N', 4294967295)],
            [42, pack('N', 42)],
        ];
    }
}
