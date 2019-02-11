<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\SignedShortInteger,
    Value,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Filesystem\Stream\StringStream;
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
    public function testFromStream($expected, $string)
    {
        $value = SignedShortInteger::fromStream(new StringStream($string));

        $this->assertInstanceOf(SignedShortInteger::class, $value);
        $this->assertSame($expected, $value->original()->value());
        $this->assertSame($string, (string) $value);
    }

    public function testThrowWhenIntegerTooHigh()
    {
        $this->assertSame(
            '[-32768;32767]',
            (string) SignedShortInteger::definitionSet()
        );
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
