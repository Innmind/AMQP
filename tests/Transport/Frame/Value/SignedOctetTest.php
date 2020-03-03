<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\SignedOctet,
    Transport\Frame\Value,
    Exception\OutOfRangeValue,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
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
        $this->assertSame($expected, $value->pack());
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($string, $expected)
    {
        $value = SignedOctet::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(SignedOctet::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, $value->pack());
    }

    public function testThrowWhenStringTooHigh()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('128 ∉ [-128;127]');

        SignedOctet::of(new Integer(128));
    }

    public function testThrowWhenStringTooLow()
    {
        $this->expectException(OutOfRangeValue::class);
        $this->expectExceptionMessage('-129 ∉ [-128;127]');

        SignedOctet::of(new Integer(-129));
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
