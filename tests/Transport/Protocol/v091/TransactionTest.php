<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\Transport\{
    Protocol\v091\Transaction,
    Protocol\Transaction as TransactionInterface,
    Frame,
    Frame\Type,
    Frame\Channel,
    Frame\Method
};
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(TransactionInterface::class, new Transaction);
    }

    public function testSelect()
    {
        $frame = (new Transaction)->select(
            $channel = new Channel(1)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(90, 10)));
        $this->assertCount(0, $frame->values());
    }

    public function testCommit()
    {
        $frame = (new Transaction)->commit(
            $channel = new Channel(1)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(90, 20)));
        $this->assertCount(0, $frame->values());
    }

    public function testRollback()
    {
        $frame = (new Transaction)->rollback(
            $channel = new Channel(1)
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method(), $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->method()->equals(new Method(90, 30)));
        $this->assertCount(0, $frame->values());
    }
}
