<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\ReplyTo;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class ReplyToTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', ReplyTo::of('foo')->toString());
    }
}
