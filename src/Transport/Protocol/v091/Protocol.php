<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\Transport\{
    Protocol as ProtocolInterface,
    Protocol\Version,
    Protocol\Connection as ConnectionInterface,
    Protocol\Exchange as ExchangeInterface,
    Protocol\Queue as QueueInterface,
    Protocol\Basic as BasicInterface,
    Protocol\Transaction as TransactionInterface,
    Frame\Method
};
use Innmind\Immutable\{
    Str,
    StreamInterface
};

final class Protocol implements ProtocolInterface
{
    private $version;
    private $read;
    private $connection;
    private $exchange;
    private $queue;
    private $basic;
    private $transaction;

    public function __construct()
    {
        $this->version = new Version(0, 9, 1);
        $this->read = new Reader;
        $this->connection = new Connection;
        $this->exchange = new Exchange;
        $this->queue = new Queue;
        $this->basic = new Basic;
        $this->transaction = new Transaction;
    }

    public function version(): Version
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function read(Method $method, Str $arguments): StreamInterface
    {
        return ($this->read)($method, $arguments);
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function exchange(): ExchangeInterface
    {
        return $this->exchange;
    }

    public function queue(): QueueInterface
    {
        return $this->queue;
    }

    public function basic(): BasicInterface
    {
        return $this->basic;
    }

    public function transaction(): TransactionInterface
    {
        return $this->transaction;
    }
}
