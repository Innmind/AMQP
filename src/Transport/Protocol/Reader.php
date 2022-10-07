<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\{
    Frame\Method,
    Frame\Value,
    Protocol\Reader\Connection,
    Protocol\Reader\Channel,
    Protocol\Reader\Exchange,
    Protocol\Reader\Queue,
    Protocol\Reader\Basic,
    Protocol\Reader\Transaction,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\Sequence;

final class Reader
{
    private Connection $connection;
    private Channel $channel;
    private Exchange $exchange;
    private Queue $queue;
    private Basic $basic;
    private Transaction $tx;

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
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
    {
        /**
         * @psalm-suppress MixedFunctionCall
         * @var Sequence<Value>
         */
        return ($this->{Methods::class($method)})($method, $arguments);
    }
}
