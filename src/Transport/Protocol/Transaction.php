<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\Transport\{
    Frame,
    Frame\Channel as FrameChannel,
};

interface Transaction
{
    public function select(FrameChannel $channel): Frame;
    public function commit(FrameChannel $channel): Frame;
    public function rollback(FrameChannel $channel): Frame;
}
