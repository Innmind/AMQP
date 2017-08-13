<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame\Value;

use Innmind\AMQP\Transport\Frame\{
    Value\Timestamp,
    Value
};
use Innmind\TimeContinuum\PointInTime\Earth\Now;
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
        $this->assertSame(
            pack('J', time()),
            (string) new Timestamp(new Now)
        );
    }
}
