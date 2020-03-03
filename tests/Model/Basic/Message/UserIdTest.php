<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\UserId;
use PHPUnit\Framework\TestCase;

class UserIdTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', (new UserId('foo'))->toString());
    }
}
