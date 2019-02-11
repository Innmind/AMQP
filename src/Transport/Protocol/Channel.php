<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Model\Channel\Flow,
    Model\Channel\FlowOk,
    Model\Channel\Close,
    Transport\Frame,
    Transport\Frame\Channel as FrameChannel,
};

interface Channel
{
    public function open(FrameChannel $channel): Frame;
    public function flow(FrameChannel $channel, Flow $command): Frame;
    public function flowOk(FrameChannel $channel, FlowOk $command): Frame;
    public function close(FrameChannel $channel, Close $command): Frame;
    public function closeOk(FrameChannel $channel): Frame;
}
