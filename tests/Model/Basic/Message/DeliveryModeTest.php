<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\DeliveryMode;
use PHPUnit\Framework\TestCase;

class DeliveryModeTest extends TestCase
{
    public function testNonPersistent()
    {
        $this->assertInstanceOf(DeliveryMode::class, DeliveryMode::nonPersistent());
        $this->assertSame(DeliveryMode::nonPersistent(), DeliveryMode::nonPersistent());
        $this->assertSame(1, DeliveryMode::nonPersistent()->toInt());
    }

    public function testPersistent()
    {
        $this->assertInstanceOf(DeliveryMode::class, DeliveryMode::persistent());
        $this->assertSame(DeliveryMode::persistent(), DeliveryMode::persistent());
        $this->assertSame(2, DeliveryMode::persistent()->toInt());
    }
}
