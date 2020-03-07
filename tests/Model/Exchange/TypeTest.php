<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Exchange;

use Innmind\AMQP\Model\Exchange\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('foo', (new Type('foo'))->toString());
    }

    public function testDirect()
    {
        $type = Type::direct();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame('direct', $type->toString());
    }

    public function testFanout()
    {
        $type = Type::fanout();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame('fanout', $type->toString());
    }

    public function testTopic()
    {
        $type = Type::topic();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame('topic', $type->toString());
    }

    public function testHeaders()
    {
        $type = Type::headers();

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame('headers', $type->toString());
    }
}
