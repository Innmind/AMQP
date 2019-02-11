<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedOctet,
    Value,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Filesystem\Stream\StringStream;
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
    public function testFromStream($string, $expected)
    {
        $value = SignedOctet::fromStream(new StringStream($string));

        $this->assertInstanceOf(SignedOctet::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    public function testThrowWhenStringTooHigh()
    {
        $this->assertSame(
            '[-128;127]',
            (string) SignedOctet::definitionSet()
        );
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
