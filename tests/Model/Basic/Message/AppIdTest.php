<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\AppId;
use PHPUnit\Framework\TestCase;

class AppIdTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', (new AppId('foo'))->toString());
    }
}
