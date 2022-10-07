<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\{
    Frame\Method,
    Frame\MethodClass,
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
    private Transaction $transaction;

    public function __construct()
    {
        $this->connection = new Connection;
        $this->channel = new Channel;
        $this->exchange = new Exchange;
        $this->queue = new Queue;
        $this->basic = new Basic;
        $this->transaction = new Transaction;
    }

    /**
     * @return Sequence<Value>
     */
    public function __invoke(Method $method, Readable $arguments): Sequence
    {
        return match ($method->class()) {
            MethodClass::connection => ($this->connection)($method, $arguments),
            MethodClass::channel => ($this->channel)($method, $arguments),
            MethodClass::exchange => ($this->exchange)($method, $arguments),
            MethodClass::queue => ($this->queue)($method, $arguments),
            MethodClass::basic => ($this->basic)($method, $arguments),
            MethodClass::transaction => ($this->transaction)($method, $arguments),
        };
    }
}
