<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model;

use Innmind\AMQP\Model\Count;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class CountTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame(0, Count::of(0)->toInt());
        $this->assertSame(42, Count::of(42)->toInt());
    }
}
