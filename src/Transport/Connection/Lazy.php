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
use Innmind\Url\UrlInterface;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriod,
};

final class Lazy implements ConnectionInterface
{
    private $transport;
    private $server;
    private $protocol;
    private $timeout;
    private $clock;
    private $connection;
    private $closed = false;

    public function __construct(
        Transport $transport,
        UrlInterface $server,
        Protocol $protocol,
        ElapsedPeriod $timeout,
        TimeContinuumInterface $clock
    ) {
        $this->transport = $transport;
        $this->server = $server;
        $this->protocol = $protocol;
        $this->timeout = $timeout;
        $this->clock = $clock;
    }

    public function protocol(): Protocol
    {
        return $this->connection()->protocol();
    }

    /**
     * {@inheritdoc}
     */
    public function send(Frame $frame): ConnectionInterface
    {
        return $this->connection()->send($frame);
    }

    /**
     * {@inheritdoc}
     */
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

        return $this->connection ?? $this->connection = new Connection(
            $this->transport,
            $this->server,
            $this->protocol,
            $this->timeout,
            $this->clock
        );
    }
}
