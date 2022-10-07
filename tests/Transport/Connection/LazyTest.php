<?php
declare(strict_types = 1);

namespace Tests\Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Transport\Connection\Lazy,
    Transport\Connection,
    Transport\Protocol,
    Transport\Protocol\ArgumentTranslator,
    Transport\Frame,
    Exception\ConnectionClosed,
};
use Innmind\Socket\Internet\Transport;
use Innmind\Url\Url;
use Innmind\TimeContinuum\Earth\{
    Clock,
    ElapsedPeriod,
};
use Innmind\OperatingSystem\{
    Remote,
    Sockets,
};
use Innmind\Server\Control\Server;
use PHPUnit\Framework\TestCase;

class LazyTest extends TestCase
{
    public function testInterface()
    {
        $connection = new Lazy(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5673/'), //wrong port on purpose
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            $this->createMock(Remote::class),
            $this->createMock(Sockets::class),
        );

        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function testConnectWhenAccessingProtocol()
    {
        try {
            $connection = new Lazy(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5673/'), //wrong port on purpose
                $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Clock,
                Remote\Generic::of($this->createMock(Server::class), new Clock),
                Sockets\Unix::of(),
            );
            $connection->protocol();
            $this->fail('it should fail');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e);
        }
    }

    public function testConnectWhenSendingFrame()
    {
        try {
            $connection = new Lazy(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5673/'), //wrong port on purpose
                $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Clock,
                Remote\Generic::of($this->createMock(Server::class), new Clock),
                Sockets\Unix::of(),
            );
            $connection->send(Frame::heartbeat());
            $this->fail('it should fail');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e);
        }
    }

    public function testConnectWhenWaitingAFrame()
    {
        try {
            $connection = new Lazy(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5673/'), //wrong port on purpose
                $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Clock,
                Remote\Generic::of($this->createMock(Server::class), new Clock),
                Sockets\Unix::of(),
            );
            $connection->wait();
            $this->fail('it should fail');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e);
        }
    }

    public function testConnectWhenAccessingMaxFrameSize()
    {
        try {
            $connection = new Lazy(
                Transport::tcp(),
                Url::of('//guest:guest@localhost:5673/'), //wrong port on purpose
                $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
                new ElapsedPeriod(1000),
                new Clock,
                Remote\Generic::of($this->createMock(Server::class), new Clock),
                Sockets\Unix::of(),
            );
            $connection->maxFrameSize();
            $this->fail('it should fail');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e);
        }
    }

    public function testDoesntConnectWhenCheckingIfClosed()
    {
        $remote = $this->createMock(Remote::class);
        $remote
            ->expects($this->never())
            ->method('socket');

        $connection = new Lazy(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5673/'), //wrong port on purpose
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            $remote,
            $this->createMock(Sockets::class),
        );

        $this->assertFalse($connection->closed());
    }

    public function testDoesntConnectOnClose()
    {
        $remote = $this->createMock(Remote::class);
        $remote
            ->expects($this->never())
            ->method('socket');

        $connection = new Lazy(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5673/'), //wrong port on purpose
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            $remote,
            $this->createMock(Sockets::class),
        );

        $this->assertNull($connection->close());
        $this->assertTrue($connection->closed());
    }

    public function testThrowWithoutConnectAttemptWhenSendingFrameAfterClose()
    {
        $remote = $this->createMock(Remote::class);
        $remote
            ->expects($this->never())
            ->method('socket');

        $connection = new Lazy(
            Transport::tcp(),
            Url::of('//guest:guest@localhost:5673/'), //wrong port on purpose
            $protocol = new Protocol($this->createMock(ArgumentTranslator::class)),
            new ElapsedPeriod(1000),
            new Clock,
            $remote,
            $this->createMock(Sockets::class),
        );

        $connection->close();

        $this->expectException(ConnectionClosed::class);
        $connection->send(Frame::heartbeat());
    }
}
