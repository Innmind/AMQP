<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\UserId;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class UserIdTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertSame('foo', UserId::of('foo')->toString());
    }
}
