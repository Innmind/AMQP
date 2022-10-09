<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Timestamp,
    Value,
};
use Innmind\TimeContinuum\{
    Earth\PointInTime\Now,
    Earth\PointInTime\PointInTime,
    PointInTime as PointInTimeInterface,
};
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class TimestampTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            Timestamp::of(new Now),
        );
    }

    public function testStringCast()
    {
        $value = Timestamp::of($now = new Now);
        $this->assertSame(\pack('J', \time()), $value->pack()->toString());
        $this->assertSame($now, $value->original());
    }

    public function testFromStream()
    {
        $value = Timestamp::unpack(Stream::ofContent(\pack('J', $time = \time())))->match(
            static fn($value) => $value,
            static fn() => null,
        );

        $this->assertInstanceOf(Timestamp::class, $value);
        $this->assertInstanceOf(PointInTimeInterface::class, $value->original());
        $this->assertTrue(
            $value->original()->equals(
                new PointInTime(\date(\DateTime::ATOM, $time)),
            ),
        );
        $this->assertSame(\pack('J', $time), $value->pack()->toString());
    }
}
