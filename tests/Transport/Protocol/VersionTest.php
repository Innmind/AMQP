<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Transport\Protocol\Version,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function testThrowWhenInvalidMajorVersion()
    {
        $this->expectException(DomainException::class);

        new Version(-1, 0, 0);
    }

    public function testThrowWhenInvalidMinorVersion()
    {
        $this->expectException(DomainException::class);

        new Version(0, -1, 0);
    }

    public function testThrowWhenInvalidFixVersion()
    {
        $this->expectException(DomainException::class);

        new Version(0, 0, -1);
    }

    public function testStringCast()
    {
        $this->assertSame("AMQP\x00\x00\x09\x01", (new Version(0, 9, 1))->pack()->toString());
    }

    public function testCompatbleWith()
    {
        $this->assertTrue((new Version(0, 9, 0))->compatibleWith(new Version(0, 9, 0)));
        $this->assertTrue((new Version(0, 9, 1))->compatibleWith(new Version(0, 9, 0)));
        $this->assertTrue((new Version(0, 9, 1))->compatibleWith(new Version(0, 9, 1)));
        $this->assertTrue((new Version(0, 9, 1))->compatibleWith(new Version(0, 9, 2)));
        $this->assertTrue((new Version(1, 0, 0))->compatibleWith(new Version(1, 0, 0)));
        $this->assertTrue((new Version(1, 1, 0))->compatibleWith(new Version(1, 0, 0)));
        $this->assertFalse((new Version(0, 8, 0))->compatibleWith(new Version(0, 9, 0)));
        $this->assertFalse((new Version(0, 8, 0))->compatibleWith(new Version(1, 0, 0)));
        $this->assertFalse((new Version(1, 0, 0))->compatibleWith(new Version(1, 1, 0)));
        $this->assertFalse((new Version(1, 0, 0))->compatibleWith(new Version(2, 0, 0)));
    }
}
