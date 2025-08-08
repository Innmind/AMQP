<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\{
    Model\Basic\Message\ContentEncoding,
    Exception\DomainException,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class ContentEncodingTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertSame('gzip', ContentEncoding::of('gzip')->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testThrowWhenInvalidContentEncoding()
    {
        $this->expectException(DomainException::class);

        ContentEncoding::of('foo bar');
    }
}
