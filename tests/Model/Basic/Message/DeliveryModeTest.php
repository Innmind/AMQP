<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\DeliveryMode;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class DeliveryModeTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testNonPersistent()
    {
        $this->assertSame(1, DeliveryMode::nonPersistent->toInt());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testPersistent()
    {
        $this->assertSame(2, DeliveryMode::persistent->toInt());
    }
}
