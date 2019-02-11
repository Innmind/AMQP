<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client;

use Innmind\AMQP\{
    Client\Logger,
    Client\Channel,
    Client,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $logger = new Logger(
            $client = $this->createMock(Client::class),
            $this->createMock(LoggerInterface::class)
        );
        $client
            ->expects($this->once())
            ->method('close');
        $client
            ->expects($this->once())
            ->method('closed')
            ->willReturn(false);

        $this->assertInstanceOf(Client::class, $logger);
        $this->assertInstanceOf(Channel\Logger::class, $logger->channel());
        $this->assertFalse($logger->closed());
        $this->assertNull($logger->close());
    }
}
