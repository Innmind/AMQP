<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\{
    Model\Basic\Message\ContentType,
    Exception\DomainException,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class ContentTypeTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertSame(
            'application/json',
            ContentType::of('application', 'json')->toString(),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testThrowWhenInvalidContentType()
    {
        $this->expectException(DomainException::class);

        ContentType::of('foo', 'json');
    }
}
