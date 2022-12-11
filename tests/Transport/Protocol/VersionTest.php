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
    public function testStringCast()
    {
        $this->assertSame("AMQP\x00\x00\x09\x01", Version::v091->pack()->toString());
        $this->assertSame("AMQP\x00\x00\x09\x00", Version::v090->pack()->toString());
    }
}
