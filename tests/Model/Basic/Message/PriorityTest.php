<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\{
    Model\Basic\Message\Priority,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class PriorityTest extends TestCase
{
    /**
     * @dataProvider priorities
     */
    public function testInterface($int)
    {
        $this->assertSame($int, (new Priority($int))->toInt());
    }

    public function testThrowWhenPriorityTooLow()
    {
        $this->expectException(DomainException::class);

        new Priority(-1);
    }

    public function testThrowWhenPriorityTooHigh()
    {
        $this->expectException(DomainException::class);

        new Priority(10);
    }

    public function testTheHigherTheValueTheHigherThePriority()
    {
        $this->assertInstanceOf(Priority::class, Priority::lowest());
        $this->assertInstanceOf(Priority::class, Priority::highest());
        $this->assertSame(0, Priority::lowest()->toInt());
        $this->assertSame(9, Priority::highest()->toInt());
    }

    public function priorities(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
            [9],
        ];
    }
}
