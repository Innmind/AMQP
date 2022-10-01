<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedLongLongInteger,
    Value,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class SignedLongLongIntegerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new SignedLongLongInteger(new Integer(0)),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testStringCast($int, $expected)
    {
        $value = new SignedLongLongInteger($int = new Integer($int));
        $this->assertSame($expected, $value->pack());
        $this->assertSame($int, $value->original());
    }

    /**
     * @dataProvider cases
     */
    public function testFromStream($expected, $string)
    {
        $value = SignedLongLongInteger::unpack(Stream::ofContent($string));

        $this->assertInstanceOf(SignedLongLongInteger::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, $value->pack());
    }

    public function cases(): array
    {
        return [
            [0, \pack('q', 0)],
            [-2147483648, \pack('q', -2147483648)],
            [-2147483649, \pack('q', -2147483649)],
            [2147483647, \pack('q', 2147483647)],
            [2147483648, \pack('q', 2147483648)],
            [42, \pack('q', 42)],
        ];
    }
}
