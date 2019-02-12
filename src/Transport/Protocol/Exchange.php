<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Model\Exchange\Declaration,
    Model\Exchange\Deletion,
    Transport\Frame,
    Transport\Frame\Channel as FrameChannel,
};

interface Exchange
{
    public function declare(FrameChannel $channel, Declaration $command): Frame;
    public function delete(FrameChannel $channel, Deletion $command): Frame;
}
