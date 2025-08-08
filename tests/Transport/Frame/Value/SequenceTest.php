<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\{
    Transport\Frame\Value\Sequence,
    Transport\Frame\Value\LongString,
    Transport\Frame\Value,
};
use Innmind\TimeContinuum\Clock;
use Innmind\IO\IO;
use Innmind\Immutable\{
    Sequence as Seq,
    Str,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{
    DataProvider,
    Group,
};

class SequenceTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(Value::class, Sequence::of());
    }

    #[Group('ci')]
    #[Group('local')]
    #[DataProvider('cases')]
    public function testStringCast($expected, $values)
    {
        $value = Sequence::of(...$values);
        $this->assertSame($expected, $value->pack()->toString());
        $this->assertInstanceOf(Seq::class, $value->original());
        $this->assertSame($values, $value->original()->toList());
    }

    #[Group('ci')]
    #[Group('local')]
    #[DataProvider('cases')]
    public function testFromStream($string, $expected)
    {
        $tmp = \fopen('php://temp', 'w+');
        \fwrite($tmp, $string);
        \fseek($tmp, 0);

        $value = IO::fromAmbientAuthority()
            ->streams()
            ->acquire($tmp)
            ->read()
            ->toEncoding(Str\Encoding::ascii)
            ->frames(Sequence::frame(Clock::live()))
            ->one()
            ->match(
                static fn($value) => $value->unwrap(),
                static fn() => null,
            );

        $this->assertInstanceOf(Sequence::class, $value);
        $this->assertCount(\count($expected), $value->original());

        foreach ($expected as $i => $v) {
            $this->assertInstanceOf(
                \get_class($v),
                $value->original()->get($i)->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $this->assertSame(
                $v->pack()->toString(),
                $value->original()->get($i)->match(
                    static fn($value) => $value->pack()->toString(),
                    static fn() => null,
                ),
            );
        }

        $this->assertSame($string, $value->pack()->toString());
    }

    public static function cases(): array
    {
        return [
            [
                \pack('N', 8).'S'.\pack('N', 3).'foo',
                [LongString::literal('foo')],
            ],
            [
                \pack('N', 20).'S'.\pack('N', 3).'fooS'.\pack('N', 7).'🙏bar',
                [
                    LongString::literal('foo'),
                    LongString::literal('🙏bar'),
                ],
            ],
        ];
    }
}
