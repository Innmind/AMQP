<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection\Logger,
    Transport\Connection,
    Transport\Frame,
    Transport\Frame\Method,
    Transport\Protocol,
    Transport\Protocol\ArgumentTranslator,
    Model\Connection\MaxFrameSize,
};
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\{
    Maybe,
    Sequence,
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
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testSend()
    {
        $connection = new Logger(
            $inner = $this->createMock(Connection::class),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $frames = static fn() => Sequence::of(Frame::heartbeat());
        $inner
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function($frames) {
                $frames(
                    new Protocol(
                        $this->createMock(Clock::class),
                        $this->createMock(ArgumentTranslator::class),
                    ),
                    MaxFrameSize::unlimited(),
                );

                return true;
            }))
            ->willReturn(Maybe::just($inner));
        $uuid = null;
        $logger
            ->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [
                    'AMQP frame about to be sent',
                    $this->callback(static function(array $context) use (&$uuid): bool {
                        $uuid = $context['uuid'];

                        return \is_string($context['uuid']) &&
                            $context['type'] === 8 &&
                            $context['channel'] === 0;
                    }),
                ],
                [
                    'AMQP frame sent',
                    $this->callback(static function(array $context) use (&$uuid): bool {
                        return $context['uuid'] === $uuid;
                    }),
                ],
            );

        $this->assertSame($connection, $connection->send($frames)->match(
            static fn($connection) => $connection,
            static fn() => null,
        ));
    }

    public function testWait()
    {
        $connection = new Logger(
            $inner = $this->createMock(Connection::class),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $frame = Frame::heartbeat();
        $inner
            ->expects($this->once())
            ->method('wait')
            ->with(Method::basicGetOk, Method::basicGetEmpty)
            ->willReturn($frame);
        $logger
            ->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [
                    'Waiting for AMQP frame',
                    ['names' => [Method::basicGetOk, Method::basicGetEmpty]],
                ],
                [
                    'AMQP frame received',
                    [
                        'type' => 8,
                        'channel' => 0,
                    ],
                ],
            );

        $this->assertSame($frame, $connection->wait(Method::basicGetOk, Method::basicGetEmpty));
    }

    public function testClose()
    {
        $connection = new Logger(
            $inner = $this->createMock(Connection::class),
            $logger = $this->createMock(LoggerInterface::class),
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
            $this->createMock(LoggerInterface::class),
        );
        $inner
            ->expects($this->exactly(2))
            ->method('closed')
            ->will($this->onConsecutiveCalls(false, true));

        $this->assertFalse($connection->closed());
        $this->assertTrue($connection->closed());
    }
}
