<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedShortInteger,
    Value
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class SignedShortIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new SignedShortInteger(new Integer(0))
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = new SignedShortInteger($int = new Integer($int));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromString($expected, $string)
    {
        $value = SignedShortInteger::fromString(new Str($string));

        $this->assertInstanceOf(SignedShortInteger::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($_, $string)
    {
        $str = SignedShortInteger::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage 32768 ∉ [-32768;32767]∩ℤ
     */
    public function testThrowWhenIntegerTooHigh()
    {
        new SignedShortInteger(new Integer(32768));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage -32769 ∉ [-32768;32767]∩ℤ
     */
    public function testThrowWhenIntegerTooLow()
    {
        new SignedShortInteger(new Integer(-32769));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\StringNotOfExpectedLength
     * @expectedExceptionMessage String "foo" is expected of being 2 characters, got 3
     */
    public function testThrowWhenStringNotOfExpectedLength()
    {
        SignedShortInteger::fromString(new Str('foo'));
    }

    public function cases(): array
    {
        return [
            [0, pack('s', 0)],
            [-32768, pack('s', -32768)],
            [32767, pack('s', 32767)],
            [42, pack('s', 42)],
        ];
    }
}
