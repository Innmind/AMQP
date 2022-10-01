<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Client\Channel;

use Innmind\AMQP\Client\{
    Channel\Logger,
    Channel,
    Channel\Exchange,
    Channel\Queue,
    Channel\Basic,
    Channel\Transaction,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $logger = new Logger(
            $channel = $this->createMock(Channel::class),
            $this->createMock(LoggerInterface::class),
        );
        $channel
            ->expects($this->once())
            ->method('closed')
            ->willReturn(false);
        $channel
            ->expects($this->once())
            ->method('close');

        $this->assertInstanceOf(Channel::class, $logger);
        $this->assertInstanceOf(Exchange::class, $logger->exchange());
        $this->assertInstanceOf(Queue::class, $logger->queue());
        $this->assertInstanceOf(Basic\Logger::class, $logger->basic());
        $this->assertInstanceOf(Transaction::class, $logger->transaction());
        $this->assertFalse($logger->closed());
        $this->assertNull($logger->close());
    }
}
