<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\{
    Model\Basic\Message\ContentType,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class ContentTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame(
            'application/json',
            (string) new ContentType('application', 'json')
        );
    }

    public function testThrowWhenInvalidContentType()
    {
        $this->expectException(DomainException::class);

        new ContentType('foo', 'json');
    }
}
