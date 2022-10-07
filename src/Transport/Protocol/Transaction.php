<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Model\Transaction\Select,
    Model\Transaction\Commit,
    Model\Transaction\Rollback,
    Transport\Frame,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Type,
    Transport\Frame\Method,
};

final class Transaction
{
    public function select(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Method::transactionSelect,
        );
    }

    public function commit(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Method::transactionCommit,
        );
    }

    public function rollback(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Method::transactionRollback,
        );
    }
}
