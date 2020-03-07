<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

use Innmind\AMQP\Client\Channel as ChannelInterface;

final class NullChannel implements ChannelInterface
{
    public function exchange(): Exchange
    {
        return new Exchange\NullExchange;
    }

    public function queue(): Queue
    {
        return new Queue\NullQueue;
    }

    public function basic(): Basic
    {
        return new Basic\NullBasic;
    }

    public function transaction(): Transaction
    {
        return new Transaction\NullTransaction;
    }

    public function closed(): bool
    {
        return true;
    }

    public function close(): void
    {
        // pass
    }
}
