<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model;

use Innmind\AMQP\Model\Count;
use PHPUnit\Framework\TestCase;

class CountTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame(0, (new Count(0))->toInt());
        $this->assertSame(42, (new Count(42))->toInt());
    }

    /**
     * @expectedException Innmind\AMQP\Exception\DomainException
     */
    public function testThrowWhenNegativeCount()
    {
        new Count(-1);
    }
}
