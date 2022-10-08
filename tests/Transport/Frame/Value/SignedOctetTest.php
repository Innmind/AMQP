<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\SignedOctet,
    Transport\Frame\Value,
};
use Innmind\Math\Exception\OutOfDefinitionSet;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class SignedOctetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, SignedOctet::of(0));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $octet)
    {
        $value = SignedOctet::of($octet);
        $this->assertSame($expected, $value->pack());
        $this->assertSame($octet, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($string, $expected)
    {
        $value = SignedOctet::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(SignedOctet::class, $value);
        $this->assertSame($expected, $value->original());
        $this->assertSame($string, $value->pack());
    }

    public function testThrowWhenStringTooHigh()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('128 ∉ [-128;127]');

        SignedOctet::of(128);
    }

    public function testThrowWhenStringTooLow()
    {
        $this->expectException(OutOfDefinitionSet::class);
        $this->expectExceptionMessage('-129 ∉ [-128;127]');

        SignedOctet::of(-129);
    }

    public function cases(): array
    {
        return [
            [\pack('c', 0), 0],
            [\pack('c', 127), 127],
            [\pack('c', -128), -128],
        ];
    }
}
