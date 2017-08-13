<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedOctet,
    Value
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class SignedOctetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, new SignedOctet(0));
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($expected, $octet)
    {
        $this->assertSame(
            $expected,
            (string) new SignedOctet($octet)
        );
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between -128 and 127, got 128
     */
    public function testThrowWhenStringTooHigh()
    {
        new SignedOctet(128);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\OutOfRangeValue
     * @expectedExceptionMessage Expected value between -128 and 127, got -129
     */
    public function testThrowWhenStringTooLow()
    {
        new SignedOctet(-129);
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
