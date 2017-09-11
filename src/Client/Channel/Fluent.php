<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

use Innmind\AMQP\Client\Channel as ChannelInterface;

final class Fluent implements ChannelInterface
{
    private $channel;

    public function __construct(ChannelInterface $channel)
    {
        $this->channel = $channel;
    }

    public function exchange(): Exchange
    {
        return $this->channel->exchange();
    }

    public function queue(): Queue
    {
        return $this->channel->queue();
    }

    public function basic(): Basic
    {
        return $this->channel->basic();
    }

    public function transaction(): Transaction
    {
        return $this->channel->transaction();
    }

    public function closed(): bool
    {
        return $this->channel->closed();
    }

    public function close(): void
    {
        $this->channel->close();
        $this->channel = new NullChannel;
    }
}
