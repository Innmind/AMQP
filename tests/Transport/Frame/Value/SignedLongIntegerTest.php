<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedLongInteger,
    Value,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Filesystem\Stream\StringStream;
use PHPUnit\Framework\TestCase;

class SignedLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new SignedLongInteger(new Integer(0))
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = new SignedLongInteger($int = new Integer($int));
        $this->assertSame($expected, (string) $value);
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = SignedLongInteger::fromStream(new StringStream($string));

        $this->assertInstanceOf(SignedLongInteger::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    public function testThrowWhenIntegerTooHigh()
    {
        $this->assertSame(
            '[-2147483648;2147483647]',
            (string) SignedLongInteger::definitionSet()
        );
    }

    public function cases(): array
    {
        return [
            [0, pack('l', 0)],
            [-2147483648, pack('l', -2147483648)],
            [2147483647, pack('l', 2147483647)],
            [42, pack('l', 42)],
        ];
    }
}
