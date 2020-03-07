<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Model\Transaction\Select,
    Model\Transaction\Commit,
    Model\Transaction\Rollback,
    Transport\Frame,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Type,
    Transport\Protocol\Transaction as TransactionInterface,
};

final class Transaction implements TransactionInterface
{
    public function select(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('tx.select'),
        );
    }

    public function commit(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('tx.commit'),
        );
    }

    public function rollback(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('tx.rollback'),
        );
    }
}
