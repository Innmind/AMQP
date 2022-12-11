<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', Type::of('foo')->toString());
    }
}
