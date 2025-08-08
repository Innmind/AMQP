<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\Protocol\Version;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class VersionTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testStringCast()
    {
        $this->assertSame("AMQP\x00\x00\x09\x01", Version::v091->pack()->toString());
        $this->assertSame("AMQP\x00\x00\x09\x00", Version::v090->pack()->toString());
    }
}
