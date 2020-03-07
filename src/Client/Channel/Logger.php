<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Client\Channel;

use Innmind\AMQP\Client\Channel as ChannelInterface;
use Psr\Log\LoggerInterface;

final class Logger implements ChannelInterface
{
    private ChannelInterface $channel;
    private Basic $basic;

    public function __construct(
        ChannelInterface $channel,
        LoggerInterface $logger
    ) {
        $this->channel = $channel;
        $this->basic = new Basic\Logger(
            $channel->basic(),
            $logger,
        );
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
        return $this->basic;
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
    }
}
