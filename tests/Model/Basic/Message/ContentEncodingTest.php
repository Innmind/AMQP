<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\{
    Model\Basic\Message\ContentEncoding,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class ContentEncodingTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('gzip', (new ContentEncoding('gzip'))->toString());
    }

    public function testThrowWhenInvalidContentEncoding()
    {
        $this->expectException(DomainException::class);

        new ContentEncoding('foo bar');
    }
}
