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
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Transaction
{
    /**
     * @return Sequence<Frame>
     */
    public function select(FrameChannel $channel): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::transactionSelect,
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function commit(FrameChannel $channel): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::transactionCommit,
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function rollback(FrameChannel $channel): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::transactionRollback,
        ));
    }
}
