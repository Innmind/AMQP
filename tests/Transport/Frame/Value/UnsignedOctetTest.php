<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value,
    Exception\OutOfRangeValue,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class UnsignedOctetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new UnsignedOctet(new Integer(0))
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $octet)
    {
        $value = new UnsignedOctet($int = new Integer($octet));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($string, $expected)
    {
        $value = UnsignedOctet::fromStream(Stream::ofContent($string));

        $this->assertInstanceOf(UnsignedOctet::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    public function testThrowWhenStringTooHigh()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('256 ∉ [0;255]');

        UnsignedOctet::of(new Integer(256));
    }

    public function testThrowWhenStringTooLow()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('-1 ∉ [0;255]');

        UnsignedOctet::of(new Integer(-1));
    }

    public function cases(): array
    {
        return [
            [chr(0), 0],
            [chr(127), 127],
            [chr(255), 255],
        ];
    }
}
