<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\Transport\{
    Connection\Logger,
    Connection,
    Protocol,
    Frame,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Connection::class,
            new Logger(
                $this->createMock(Connection::class),
                $this->createMock(LoggerInterface::class)
            )
        );
    }

    public function testProtocol()
    {
        $connection = new Logger(
            $inner = $this->createMock(Connection::class),
            $this->createMock(LoggerInterface::class)
        );
        $inner
            ->expects($this->once())
            ->method('protocol')
            ->willReturn($expected = $this->createMock(Protocol::class));

        $this->assertSame($expected, $connection->protocol());
    }

    public function testSend()
    {
        $connection = new Logger(
            $inner = $this->createMock(Connection::class),
            $logger = $this->createMock(LoggerInterface::class)
        );
        $frame = Frame::heartbeat();
        $inner
            ->expects($this->once())
            ->method('send')
            ->with($frame);
        $uuid = null;
        $logger
            ->expects($this->at(0))
            ->method('debug')
            ->with(
                'AMQP frame about to be sent',
                $this->callback(function(array $context) use (&$uuid): bool {
                    $uuid = $context['uuid'];

                    return is_string($context['uuid']) &&
                        $context['type'] === 8 &&
                        $context['channel'] === 0;
                })
            );
        $logger
            ->expects($this->at(1))
            ->method('debug')
            ->with(
                'AMQP frame sent',
                $this->callback(function(array $context) use (&$uuid): bool {
                    return $context['uuid'] === $uuid;
                })
            );

        $this->assertNull($connection->send($frame));
    }

    public function testWait()
    {
        $connection = new Logger(
            $inner = $this->createMock(Connection::class),
            $logger = $this->createMock(LoggerInterface::class)
        );
        $frame = Frame::heartbeat();
        $inner
            ->expects($this->once())
            ->method('wait')
            ->with('foo', 'bar')
            ->willReturn($frame);
        $logger
            ->expects($this->at(0))
            ->method('debug')
            ->with(
                'Waiting for AMQP frame',
                ['names' => ['foo', 'bar']]
            );
        $logger
            ->expects($this->at(1))
            ->method('debug')
            ->with(
                'AMQP frame received',
                [
                    'type' => 8,
                    'channel' => 0,
                ]
            );

        $this->assertSame($frame, $connection->wait('foo', 'bar'));
    }

    public function testClose()
    {
        $connection = new Logger(
            $inner = $this->createMock(Connection::class),
            $logger = $this->createMock(LoggerInterface::class)
        );
        $inner
            ->expects($this->once())
            ->method('close');
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('AMQP connection closed');

        $this->assertNull($connection->close());
    }

    public function testClosed()
    {
        $connection = new Logger(
            $inner = $this->createMock(Connection::class),
            $this->createMock(LoggerInterface::class)
        );
        $inner
            ->expects($this->at(0))
            ->method('closed')
            ->willReturn(false);
        $inner
            ->expects($this->at(1))
            ->method('closed')
            ->willReturn(true);

        $this->assertFalse($connection->closed());
        $this->assertTrue($connection->closed());
    }
}
