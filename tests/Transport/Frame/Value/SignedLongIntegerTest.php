<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\SignedLongInteger,
    Transport\Frame\Value,
    Exception\OutOfRangeValue,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class SignedLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new SignedLongInteger(new Integer(0))
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = new SignedLongInteger($int = new Integer($int));
        $this->assertSame($expected, $value->pack());
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = SignedLongInteger::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(SignedLongInteger::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, $value->pack());
    }

    public function testThrowWhenIntegerTooHigh()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('2147483648 ∉ [-2147483648;2147483647]');

        SignedLongInteger::of(new Integer(2147483648));
    }

    public function testThrowWhenIntegerTooLow()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('-2147483649 ∉ [-2147483648;2147483647]');

        SignedLongInteger::of(new Integer(-2147483649));
    }

    public function cases(): array
    {
        return [
            [0, pack('l', 0)],
            [-2147483648, pack('l', -2147483648)],
            [2147483647, pack('l', 2147483647)],
            [42, pack('l', 42)],
        ];
    }
}
