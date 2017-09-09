<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\CorrelationId;
use PHPUnit\Framework\TestCase;

class CorrelationIdTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', (string) new CorrelationId('foo'));
    }
}
