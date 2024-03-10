<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Exception;

use Innmind\AMQP\Model\Connection\MaxChannels;

final class FrameChannelExceedAllowedChannelNumber extends LogicException
{
    public function __construct(int $channel, MaxChannels $max)
    {
        parent::__construct(\sprintf(
            'Max channel id can be %s but got %s',
            $max->toInt(),
            $channel,
        ));
    }
}
