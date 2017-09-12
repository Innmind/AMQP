<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\ContentType;
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

    /**
     * @expectedException Innmind\AMQP\Exception\DomainException
     */
    public function testThrowWhenInvalidContentType()
    {
        new ContentType('foo', 'json');
    }
}
