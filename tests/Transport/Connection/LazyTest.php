<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection\Lazy,
    Transport\Connection,
    Transport\Protocol\v091\Protocol,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame,
    Exception\ConnectionClosed,
};
use Innmind\Socket\{
    Internet\Transport,
    Exception\FailedToOpenSocket,
};
use Innmind\Url\Url;
use Innmind\TimeContinuum\{
    TimeContinuum\Earth,
    ElapsedPeriod,
};
use PHPUNit\Framework\TestCase;

class LazyTest extends TestCase
{
    public function testInterface()
    {
        $connection = new Lazy(
            Transport::tcp(),
            Url::fromString('//guest:guest@localhost:5673/'), //wrong port on purpose
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Earth
        );

        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function testConnectWhenAccessingProtocol()
    {
        try {
            $connection = new Lazy(
                Transport::tcp(),
                Url::fromString('//guest:guest@localhost:5673/'), //wrong port on purpose
                $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Earth
            );
            $connection->protocol();
            $this->fail('it should fail');
        } catch (\Exception $e) {
            $this->assertInstanceOf(FailedToOpenSocket::class, $e);
        }
    }

    public function testConnectWhenSendingFrame()
    {
        try {
            $connection = new Lazy(
                Transport::tcp(),
                Url::fromString('//guest:guest@localhost:5673/'), //wrong port on purpose
                $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Earth
            );
            $connection->send(Frame::heartbeat());
            $this->fail('it should fail');
        } catch (\Exception $e) {
            $this->assertInstanceOf(FailedToOpenSocket::class, $e);
        }
    }

    public function testConnectWhenWaitingAFrame()
    {
        try {
            $connection = new Lazy(
                Transport::tcp(),
                Url::fromString('//guest:guest@localhost:5673/'), //wrong port on purpose
                $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Earth
            );
            $connection->wait();
            $this->fail('it should fail');
        } catch (\Exception $e) {
            $this->assertInstanceOf(FailedToOpenSocket::class, $e);
        }
    }

    public function testConnectWhenAccessingMaxFrameSize()
    {
        try {
            $connection = new Lazy(
                Transport::tcp(),
                Url::fromString('//guest:guest@localhost:5673/'), //wrong port on purpose
                $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Earth
            );
            $connection->maxFrameSize();
            $this->fail('it should fail');
        } catch (\Exception $e) {
            $this->assertInstanceOf(FailedToOpenSocket::class, $e);
        }
    }

    public function testDoesntConnectWhenCheckingIfClosed()
    {
        $connection = new Lazy(
            Transport::tcp(),
            Url::fromString('//guest:guest@localhost:5673/'), //wrong port on purpose
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Earth
        );

        $this->assertFalse($connection->closed());
    }

    public function testDoesntConnectOnClose()
    {
        $connection = new Lazy(
            Transport::tcp(),
            Url::fromString('//guest:guest@localhost:5673/'), //wrong port on purpose
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Earth
        );

        $this->assertNull($connection->close());
        $this->assertTrue($connection->closed());
    }

    public function testThrowWithoutConnectAttemptWhenSendingFrameAfterClose()
    {
        $connection = new Lazy(
            Transport::tcp(),
            Url::fromString('//guest:guest@localhost:5673/'), //wrong port on purpose
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Earth
        );

        $connection->close();

        $this->expectException(ConnectionClosed::class);
        $connection->send(Frame::heartbeat());
    }
}
