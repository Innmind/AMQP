<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Decimal,
    Value
};
use Innmind\Math\Algebra\{
    Number,
    Integer
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    StreamInterface,
    Str
};
use PHPUnit\Framework\TestCase;

class DecimalTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new Decimal(new Integer(100), new Integer(2))
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($number, $scale, $expected)
    {
        $value = new Decimal(new Integer($number), new Integer($scale));
        $this->assertSame($expected, (string) $value);
        $this->assertInstanceOf(Number::class, $value->original());
        $this->assertSame("$number ÷ (10^$scale)", (string) $value->original());
        $this->assertSame($number / (10**$scale), $value->original()->value());
    }

    /**
     * @dataProvider cases
     */
    public function testFromString($number, $scale, $string)
    {
        $value = Decimal::fromString(new Str($string));

        $this->assertInstanceOf(Decimal::class, $value);
        $this->assertSame(($number / (10**$scale)), $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($number, $scale, $string)
    {
        $value = Decimal::fromStream(new StringStream($string));

        $this->assertInstanceOf(Decimal::class, $value);
        $this->assertSame(($number / (10**$scale)), $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($_, $__, $string)
    {
        $str = Decimal::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    public function cases(): array
    {
        return [
            [100, 2, chr(2).pack('l', 100)],
            [100, 0, chr(0).pack('l', 100)],
        ];
    }
}
