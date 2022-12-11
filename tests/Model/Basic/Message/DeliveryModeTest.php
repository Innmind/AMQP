<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic\Message;

use Innmind\AMQP\Model\Basic\Message\DeliveryMode;
use PHPUnit\Framework\TestCase;

class DeliveryModeTest extends TestCase
{
    public function testNonPersistent()
    {
        $this->assertSame(1, DeliveryMode::nonPersistent->toInt());
    }

    public function testPersistent()
    {
        $this->assertSame(2, DeliveryMode::persistent->toInt());
    }
}
