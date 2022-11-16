<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP;

use Innmind\AMQP\{
    Producers,
    Producer,
    Model\Exchange\Declaration,
    Model\Exchange\Type,
    Client,
};
use PHPUnit\Framework\TestCase;

class ProducersTest extends TestCase
{
    public function testInterface()
    {
        $producers = new Producers(
            $this->createMock(Client::class),
            'foo',
            'bar',
        );

        $this->assertTrue($producers->contains('foo'));
        $this->assertTrue($producers->contains('bar'));
        $this->assertFalse($producers->contains('baz'));
        $this->assertInstanceOf(Producer::class, $producers->get('foo'));
        $this->assertSame($producers->get('foo'), $producers->get('foo'));
    }

    public function testFromDeclarations()
    {
        $producers = Producers::of(
            $this->createMock(Client::class),
            Declaration::passive('foo', Type::direct),
            Declaration::passive('bar', Type::direct),
        );

        $this->assertTrue($producers->contains('foo'));
        $this->assertTrue($producers->contains('bar'));
        $this->assertFalse($producers->contains('baz'));
    }
}
