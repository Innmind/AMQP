<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model;

use Innmind\AMQP\Model\Count;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class CountTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertSame(0, Count::of(0)->toInt());
        $this->assertSame(42, Count::of(42)->toInt());
    }
}
