<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Timestamp,
    Value
};
use Innmind\TimeContinuum\{
    PointInTime\Earth\Now,
    PointInTime\Earth\PointInTime,
    PointInTimeInterface
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class TimestampTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Value::class,
            new Timestamp(new Now)
        );
    }

    public function testStringCast()
    {
        $value = new Timestamp($now = new Now);
        $this->assertSame(pack('J', time()), (string) $value);
        $this->assertSame($now, $value->original());
    }

    public function testFromString()
    {
        $value = Timestamp::fromString(new Str(pack('J', $time = time())));

        $this->assertInstanceOf(Timestamp::class, $value);
        $this->assertInstanceOf(PointInTimeInterface::class, $value->original());
        $this->assertTrue(
            $value->original()->equals(
                new PointInTime(date(\DateTime::ATOM, $time))
            )
        );
        $this->assertSame(pack('J', $time), (string) $value);
    }

    public function testFromStream()
    {
        $value = Timestamp::fromStream(new StringStream(pack('J', $time = time())));

        $this->assertInstanceOf(Timestamp::class, $value);
        $this->assertInstanceOf(PointInTimeInterface::class, $value->original());
        $this->assertTrue(
            $value->original()->equals(
                new PointInTime(date(\DateTime::ATOM, $time))
            )
        );
        $this->assertSame(pack('J', $time), (string) $value);
    }

    public function testCut()
    {
        $string = pack('J', $time = time());
        $str = Timestamp::cut(new Str($string.'foo'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame($string, (string) $str);
    }
}
