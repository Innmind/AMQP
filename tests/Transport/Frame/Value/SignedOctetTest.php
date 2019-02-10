<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedOctet,
    Value
};
use Innmind\Math\Algebra\Integer;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class SignedOctetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new SignedOctet(new Integer(0)));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $octet)
    {
        $value = new SignedOctet($int = new Integer($octet));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromString($string, $expected)
    {
        $value = SignedOctet::fromString(new Str($string));

        $this->assertInstanceOf(SignedOctet::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($string, $expected)
    {
        $value = SignedOctet::fromStream(new StringStream($string));

        $this->assertInstanceOf(SignedOctet::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    /**
     * @dataProvider cases
     */
    public function testCut($string)
    {
        $str = SignedOctet::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage 128 ∉ [-128;127]
     */
    public function testThrowWhenStringTooHigh()
    {
        new SignedOctet(new Integer(128));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage -129 ∉ [-128;127]
     */
    public function testThrowWhenStringTooLow()
    {
        new SignedOctet(new Integer(-129));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\StringNotOfExpectedLength
     * @expectedExceptionMessage String "foo" is expected of being 1 characters, got 3
     */
    public function testThrowWhenStringNotOfExpectedLength()
    {
        SignedOctet::fromString(new Str('foo'));
    }

    public function cases(): array
    {
        return [
            [pack('c', 0), 0],
            [pack('c', 127), 127],
            [pack('c', -128), -128],
        ];
    }
}
