<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\AppId;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class AppIdTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', AppId::of('foo')->toString());
    }
}
