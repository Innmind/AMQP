<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

use Innmind\AMQP\{
    Client\Channel as ChannelInterfce,
    Model\Channel\Close,
    Transport\Connection,
    Transport\Frame\Channel as Number
};

final class Channel implements ChannelInterfce
{
    private $connection;
    private $number;
    private $exchange;
    private $queue;
    private $basic;
    private $transaction;
    private $closed = false;

    public function __construct(Connection $connection, Number $number)
    {
        $this->connection = $connection;
        $this->number = $number;

        $connection
            ->send($connection->protocol()->channel()->open($number))
            ->wait('channel.open-ok');

        $this->exchange = new Exchange\Exchange($connection, $number);
        $this->queue = new Queue\Queue($connection, $number);
        $this->basic = new Basic\Basic($connection, $number);
        $this->transaction = new Transaction\Transaction($connection, $number);
    }

    public function exchange(): Exchange
    {
        return $this->exchange;
    }

    public function queue(): Queue
    {
        return $this->queue;
    }

    public function basic(): Basic
    {
        return $this->basic;
    }

    public function transaction(): Transaction
    {
        return $this->transaction;
    }

    public function closed(): bool
    {
        return $this->closed || !$this->connection->opened();
    }

    public function close(): void
    {
        if ($this->closed()) {
            return;
        }

        $this
            ->connection
            ->send($this->connection->protocol()->channel()->close(
                $this->number,
                new Close
            ))
            ->wait('channel.close-ok');
        $this->closed = true;
    }
}
