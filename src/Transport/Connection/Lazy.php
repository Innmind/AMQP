<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Model\Connection\MaxFrameSize,
    Transport\Connection as ConnectionInterface,
    Transport\Protocol,
    Transport\Frame,
    Exception\ConnectionClosed,
};
use Innmind\Socket\Internet\Transport;
use Innmind\Url\Url;
use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
};
use Innmind\OperatingSystem\{
    Remote,
    Sockets,
};

final class Lazy implements ConnectionInterface
{
    private Transport $transport;
    private Url $server;
    private Protocol $protocol;
    private ElapsedPeriod $timeout;
    private Clock $clock;
    private Remote $remote;
    private Sockets $sockets;
    private ?Connection $connection = null;
    private bool $closed = false;

    public function __construct(
        Transport $transport,
        Url $server,
        Protocol $protocol,
        ElapsedPeriod $timeout,
        Clock $clock,
        Remote $remote,
        Sockets $sockets,
    ) {
        $this->transport = $transport;
        $this->server = $server;
        $this->protocol = $protocol;
        $this->timeout = $timeout;
        $this->clock = $clock;
        $this->remote = $remote;
        $this->sockets = $sockets;
    }

    public function protocol(): Protocol
    {
        return $this->connection()->protocol();
    }

    public function send(Frame $frame): void
    {
        $this->connection()->send($frame);
    }

    public function wait(string ...$names): Frame
    {
        return $this->connection()->wait(...$names);
    }

    public function maxFrameSize(): MaxFrameSize
    {
        return $this->connection()->maxFrameSize();
    }
    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        if ($this->initialized()) {
            $this->connection()->close();
        }

        $this->closed = true;
    }

    public function closed(): bool
    {
        if ($this->initialized()) {
            return $this->connection()->closed();
        }

        return $this->closed;
    }

    private function initialized(): bool
    {
        return $this->connection instanceof Connection;
    }

    private function connection(): Connection
    {
        if ($this->closed) {
            throw new ConnectionClosed;
        }

        return $this->connection ??= new Connection(
            $this->transport,
            $this->server,
            $this->protocol,
            $this->timeout,
            $this->clock,
            $this->remote,
            $this->sockets,
        );
    }
}
