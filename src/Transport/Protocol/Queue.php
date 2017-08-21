<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Model\Queue\Declaration,
    Model\Queue\Deletion,
    Model\Queue\Binding,
    Model\Queue\Unbinding,
    Model\Queue\Purge,
    Transport\Frame,
    Transport\Frame\Channel as FrameChannel
};

interface Queue
{
    public function declare(FrameChannel $channel, Declaration $command): Frame;
    public function delete(FrameChannel $channel, Deletion $command): Frame;
    public function bind(FrameChannel $channel, Binding $command): Frame;
    public function unbind(FrameChannel $channel, Unbinding $command): Frame;
    public function purge(FrameChannel $channel, Purge $command): Frame;
}
