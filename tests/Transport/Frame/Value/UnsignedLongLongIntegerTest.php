<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedLongLongInteger,
    Value,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Filesystem\Stream\StringStream;
use PHPUnit\Framework\TestCase;

class UnsignedLongLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new UnsignedLongLongInteger(new Integer(0))
        );
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage -1 ∉ [0;+∞]
     */
    public function testThrowWhenIntegerTooLow()
    {
        new UnsignedLongLongInteger(new Integer(-1));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = new UnsignedLongLongInteger($int = new Integer($int));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = UnsignedLongLongInteger::fromStream(new StringStream($string));

        $this->assertInstanceOf(UnsignedLongLongInteger::class, $value);
        $this->assertInstanceOf(Integer::class, $value->original());
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    public function cases(): array
    {
        return [
            [0, pack('J', 0)],
            [4294967295, pack('J', 4294967295)],
            [4294967296, pack('J', 4294967296)],
            [PHP_INT_MAX, pack('J', PHP_INT_MAX)],
            [42, pack('J', 42)],
        ];
    }
}
