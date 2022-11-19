<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\{
    Protocol\Transaction,
    Frame,
    Frame\Type,
    Frame\Channel,
    Frame\Method,
};
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testSelect()
    {
        $frame = (new Transaction)->select(
            $channel = new Channel(1),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(90, 10)));
        $this->assertCount(0, $frame->values());
    }

    public function testCommit()
    {
        $frame = (new Transaction)->commit(
            $channel = new Channel(1),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(90, 20)));
        $this->assertCount(0, $frame->values());
    }

    public function testRollback()
    {
        $frame = (new Transaction)->rollback(
            $channel = new Channel(1),
        )->match(
            static fn($frame) => $frame,
            static fn() => null,
        );

        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertSame(Type::method, $frame->type());
        $this->assertSame($channel, $frame->channel());
        $this->assertTrue($frame->is(Method::of(90, 30)));
        $this->assertCount(0, $frame->values());
    }
}
