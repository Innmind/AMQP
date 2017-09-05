<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Transport\Protocol\v091\Exchange,
    Transport\Protocol\Exchange as ExchangeInterface,
    Transport\Frame,
    Transport\Frame\Channel,
    Transport\Frame\Method,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\Table,
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
    Model\Exchange\Type
};
use PHPUnit\Framework\TestCase;

class ExchangeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(ExchangeInterface::class, new Exchange);
    }

    public function testDeclare()
    {
        $frame = (new Exchange)->declare(
            $channel = new Channel(1),
            Declaration::passive('foo', Type::direct())
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Frame\Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(40, 10)));
        $this->assertCount(5, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('foo', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(2));
        $this->assertSame('direct', (string) $frame->values()->get(2)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(3));
        $this->assertSame(
            [true, false, false, false, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );
        $this->assertInstanceOf(Table::class, $frame->values()->get(4));

        $frame = (new Exchange)->declare(
            $channel = new Channel(1),
            Declaration::durable('foo', Type::direct())
        );

        $this->assertSame(
            [false, true, false, false, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );

        $frame = (new Exchange)->declare(
            $channel = new Channel(1),
            Declaration::temporary('foo', Type::direct())
        );

        $this->assertSame(
            [false, false, false, false, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );

        $frame = (new Exchange)->declare(
            $channel = new Channel(1),
            Declaration::autoDelete('foo', Type::direct())
        );

        $this->assertSame(
            [false, false, true, false, false],
            $frame->values()->get(3)->original()->toPrimitive()
        );

        $frame = (new Exchange)->declare(
            $channel = new Channel(1),
            Declaration::autoDelete('foo', Type::direct())->dontWait()
        );

        $this->assertSame(
            [false, false, true, false, true],
            $frame->values()->get(3)->original()->toPrimitive()
        );
    }

    public function testDeletion()
    {
        $frame = (new Exchange)->delete(
            $channel = new Channel(1),
            new Deletion('foo')
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Frame\Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(40, 20)));
        $this->assertCount(3, $frame->values());
        $this->assertInstanceOf(
            UnsignedShortInteger::class,
            $frame->values()->get(0)
        );
        $this->assertSame(0, $frame->values()->get(0)->original()->value());
        $this->assertInstanceOf(ShortString::class, $frame->values()->get(1));
        $this->assertSame('foo', (string) $frame->values()->get(1)->original());
        $this->assertInstanceOf(Bits::class, $frame->values()->get(2));
        $this->assertSame(
            [false, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = (new Exchange)->delete(
            $channel = new Channel(1),
            (new Deletion('foo'))->ifUnused()
        );

        $this->assertSame(
            [true, false],
            $frame->values()->get(2)->original()->toPrimitive()
        );

        $frame = (new Exchange)->delete(
            $channel = new Channel(1),
            (new Deletion('foo'))->dontWait()
        );

        $this->assertSame(
            [false, true],
            $frame->values()->get(2)->original()->toPrimitive()
        );
    }
}
