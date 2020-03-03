<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value,
    Exception\OutOfRangeValue,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class UnsignedShortIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new UnsignedShortInteger(new Integer(0))
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = new UnsignedShortInteger($int = new Integer($int));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = UnsignedShortInteger::fromStream(Stream::ofContent($string));

        $this->assertInstanceOf(UnsignedShortInteger::class, $value);
        $this->assertInstanceOf(Integer::class, $value->original());
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    public function testThrowWhenIntegerTooHigh()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('65536 ∉ [0;65535]');

        UnsignedShortInteger::of(new Integer(65536));
    }

    public function testThrowWhenIntegerTooLow()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('-1 ∉ [0;65535]');

        UnsignedShortInteger::of(new Integer(-1));
    }

    public function cases(): array
    {
        return [
            [0, pack('n', 0)],
            [42, pack('n', 42)],
            [32767, pack('n', 32767)],
            [65535, pack('n', 65535)],
        ];
    }
}
