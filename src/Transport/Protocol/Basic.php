<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Model\Basic\Ack,
    Model\Basic\Cancel,
    Model\Basic\Consume,
    Model\Basic\Get,
    Model\Basic\Publish,
    Model\Basic\Qos,
    Model\Basic\Recover,
    Model\Basic\Reject,
    Transport\Frame,
    Transport\Frame\Channel as FrameChannel
};

interface Basic
{
    public function ack(FrameChannel $channel, Ack $command): Frame;
    public function cancel(FrameChannel $channel, Cancel $command): Frame;
    public function consume(FrameChannel $channel, Consume $command): Frame;
    public function get(FrameChannel $channel, Get $command): Frame;
    public function publish(FrameChannel $channel, Publish $command): Frame;
    public function qos(FrameChannel $channel, Qos $command): Frame;
    public function recover(FrameChannel $channel, Recover $command): Frame;
    public function reject(FrameChannel $channel, Reject $command): Frame;
}
