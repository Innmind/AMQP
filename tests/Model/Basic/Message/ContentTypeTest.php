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
            ContentType::of('application', 'json')->toString(),
        );
    }

    public function testThrowWhenInvalidContentType()
    {
        $this->expectException(DomainException::class);

        ContentType::of('foo', 'json');
    }
}
