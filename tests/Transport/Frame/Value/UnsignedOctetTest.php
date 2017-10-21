<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedOctet,
    Value
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\Str;
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
    public function testFromString($string, $expected)
    {
        $value = UnsignedOctet::fromString(new Str($string));

        $this->assertInstanceOf(UnsignedOctet::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($string)
    {
        $str = UnsignedOctet::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage 256 ∉ [0;255]
     */
    public function testThrowWhenStringTooHigh()
    {
        new UnsignedOctet(new Integer(256));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage -1 ∉ [0;255]
     */
    public function testThrowWhenStringTooLow()
    {
        new UnsignedOctet(new Integer(-1));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\StringNotOfExpectedLength
     */
    public function testThrowWhenStringNotOfExpectedLength()
    {
        UnsignedOctet::fromString(new Str('foo'));
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
