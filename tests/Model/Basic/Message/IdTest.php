<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\Id;
use PHPUnit\Framework\TestCase;

class IdTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', (new Id('foo'))->toString());
    }
}
