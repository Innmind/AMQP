<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\ReplyTo;
use PHPUnit\Framework\TestCase;

class ReplyToTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', (new ReplyTo('foo'))->toString());
    }
}
