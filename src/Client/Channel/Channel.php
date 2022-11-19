<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

use Innmind\AMQP\{
    Client\Channel as ChannelInterfce,
    Model\Channel\Close,
    Transport\Connection,
    Transport\Frame\Channel as Number,
    Transport\Frame\Method,
};

final class Channel implements ChannelInterfce
{
    private Connection $connection;
    private Number $number;
    private Exchange $exchange;
    private Queue $queue;
    private Basic $basic;
    private Transaction $transaction;
    private bool $closed = false;

    private function __construct(Connection $connection, Number $number)
    {
        $this->connection = $connection;
        $this->number = $number;

        $connection = $connection
            ->send(static fn($protocol) => $protocol->channel()->open($number))
            ->wait(Method::channelOpenOk)
            ->match(
                static fn($connection) => $connection,
                static fn($connection) => $connection,
                static fn() => throw new \RuntimeException,
            );

        $this->exchange = new Exchange\Exchange($connection, $number);
        $this->queue = new Queue\Queue($connection, $number);
        $this->basic = new Basic\Basic($connection, $number);
        $this->transaction = new Transaction\Transaction($connection, $number);
    }

    public static function open(Connection $connection, Number $number): self
    {
        return new self($connection, $number);
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
        return $this->closed || $this->connection->closed();
    }

    public function close(): void
    {
        if ($this->closed()) {
            return;
        }

        $_ = $this
            ->connection
            ->send(fn($protocol) => $protocol->channel()->close(
                $this->number,
                Close::demand(),
            ))
            ->wait(Method::channelCloseOk)
            ->match(
                static fn() => null,
                static fn() => null,
                static fn() => throw new \RuntimeException,
            );
        $this->closed = true;
    }
}
