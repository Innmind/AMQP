<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\Transport\{
    Frame\Method,
    Protocol\v091\Reader\Connection,
    Protocol\v091\Reader\Channel,
    Protocol\v091\Reader\Exchange,
    Protocol\v091\Reader\Queue,
    Protocol\v091\Reader\Basic,
    Protocol\v091\Reader\Transaction,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\StreamInterface;

final class Reader
{
    private $connection;
    private $channel;
    private $exchange;
    private $queue;
    private $basic;
    private $tx;

    public function __construct()
    {
        $this->connection = new Connection;
        $this->channel = new Channel;
        $this->exchange = new Exchange;
        $this->queue = new Queue;
        $this->basic = new Basic;
        $this->tx = new Transaction;
    }

    /**
     * @return StreamInterface<Value>
     */
    public function __invoke(Method $method, Readable $arguments): StreamInterface
    {
        return ($this->{Methods::class($method)})($method, $arguments);
    }
}
