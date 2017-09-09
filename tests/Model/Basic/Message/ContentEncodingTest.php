<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\ContentEncoding;
use PHPUnit\Framework\TestCase;

class ContentEncodingTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('gzip', (string) new ContentEncoding('gzip'));
    }

    /**
     * @expectedException Innmind\AMQP\Exception\DomainException
     */
    public function testThrowWhenInvalidContentEncoding()
    {
        new ContentEncoding('foo bar');
    }
}
