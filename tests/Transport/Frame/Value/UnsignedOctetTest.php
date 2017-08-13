<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\UnsignedOctet,
    Value
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class UnsignedOctetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new UnsignedOctet(0));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $octet)
    {
        $this->assertSame(
            $expected,
            (string) new UnsignedOctet($octet)
        );
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between 0 and 255, got 256
     */
    public function testThrowWhenStringTooHigh()
    {
        new UnsignedOctet(256);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between 0 and 255, got -1
     */
    public function testThrowWhenStringTooLow()
    {
        new UnsignedOctet(-1);
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
