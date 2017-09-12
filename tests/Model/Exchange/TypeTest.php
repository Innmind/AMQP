<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Exchange;

use Innmind\AMQP\Model\Exchange\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', (string) new Type('foo'));
    }

    public function testDirect()
    {
        $type = Type::direct();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame('direct', (string) $type);
    }

    public function testFanout()
    {
        $type = Type::fanout();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame('fanout', (string) $type);
    }

    public function testTopic()
    {
        $type = Type::topic();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame('topic', (string) $type);
    }

    public function testHeaders()
    {
        $type = Type::headers();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame('headers', (string) $type);
    }
}
