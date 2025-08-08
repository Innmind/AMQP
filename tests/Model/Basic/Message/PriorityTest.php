<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\Priority;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class PriorityTest extends TestCase
{
    public function testTheHigherTheValueTheHigherThePriority()
    {
        $this->assertSame(0, Priority::lowest()->toInt());
        $this->assertSame(9, Priority::highest()->toInt());
    }
}
