<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Model\Basic\Get;
use PHPUnit\Framework\TestCase;

class GetTest extends TestCase
{
    public function testInterface()
    {
        $command = new Get('queue');

        $this->assertSame('queue', $command->queue());
        $this->assertFalse($command->shouldAutoAcknowledge());
    }

    public function testAutoAcknowledge()
    {
        $command = new Get('queue');
        $command2 = $command->autoAcknowledge();

        $this->assertInstanceOf(Get::class, $command2);
        $this->assertNotSame($command2, $command);
        $this->assertFalse($command->shouldAutoAcknowledge());
        $this->assertTrue($command2->shouldAutoAcknowledge());

        $command3 = $command2->manualAcknowledge();

        $this->assertInstanceOf(Get::class, $command3);
        $this->assertNotSame($command2, $command3);
        $this->assertTrue($command2->shouldAutoAcknowledge());
        $this->assertFalse($command3->shouldAutoAcknowledge());
    }
}
