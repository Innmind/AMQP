<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Frame;

use Innmind\AMQP\Transport\Frame\Method;
use PHPUnit\Framework\TestCase;

class MethodTest extends TestCase
{
    public function testInterface()
    {
        $method = new Method(0, 0);

        $this->assertSame(0, $method->class());
        $this->assertSame(0, $method->method());

        $method = new Method(10, 20);

        $this->assertSame(10, $method->class());
        $this->assertSame(20, $method->method());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\DomainException
     */
    public function testThrowWhenClassIdTooLow()
    {
        new Method(-1, 0);
    }

    /**
     * @expectedException Innmind\AMQP\Exception\DomainException
     */
    public function testThrowWhenMethodIdTooLow()
    {
        new Method(0, -1);
    }
}
