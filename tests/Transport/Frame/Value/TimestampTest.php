<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Timestamp,
    Value,
};
use Innmind\Time\{
    Point,
    Clock,
};
use Innmind\IO\IO;
use Innmind\Immutable\Str;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class TimestampTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            Timestamp::of(Point::now()),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testStringCast()
    {
        $value = Timestamp::of($now = Point::now());
        $this->assertSame(\pack('J', \time()), $value->pack()->toString());
        $this->assertSame($now, $value->original());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testFromStream()
    {
        $tmp = \fopen('php://temp', 'w+');
        \fwrite($tmp, \pack('J', $time = \time()));
        \fseek($tmp, 0);

        $value = IO::fromAmbientAuthority()
            ->streams()
            ->acquire($tmp)
            ->read()
            ->toEncoding(Str\Encoding::ascii)
            ->frames(Timestamp::frame(Clock::live()))
            ->one()
            ->match(
                static fn($value) => $value->unwrap(),
                static fn() => null,
            );

        $this->assertInstanceOf(Timestamp::class, $value);
        $this->assertInstanceOf(Point::class, $value->original());
        $this->assertTrue(
            $value->original()->equals(
                Point::at(new \DateTimeImmutable(
                    \date(\DateTime::ATOM, $time),
                )),
            ),
        );
        $this->assertSame(\pack('J', $time), $value->pack()->toString());
    }
}
