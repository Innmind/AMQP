<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\AMQP\{
    Model\Channel\Flow,
    Model\Channel\FlowOk,
    Model\Channel\Close,
    Transport\Frame,
    Transport\Frame\Method,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\UnsignedShortInteger,
};
use Innmind\Immutable\{
    Str,
    Sequence,
};

/**
 * @internal
 */
final class Channel
{
    /**
     * @return Sequence<Frame>
     */
    public function open(FrameChannel $channel): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::channelOpen,
            ShortString::of(Str::of('')), // out of band (reserved)
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function flow(FrameChannel $channel, Flow $command): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::channelFlow,
            Bits::of($command->active()),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function flowOk(FrameChannel $channel, FlowOk $command): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::channelFlowOk,
            Bits::of($command->active()),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function close(FrameChannel $channel, Close $command): Sequence
    {
        [$replyCode, $replyText] = $command->response()->match(
            static fn($info) => $info,
            static fn() => [0, ''],
        );

        return Sequence::of(Frame::method(
            $channel,
            Method::channelClose,
            UnsignedShortInteger::internal($replyCode),
            ShortString::of(Str::of($replyText)),
            // we don't offer the user to specify the cause of the close because
            // it implies exposing the transport details at the model level and
            // it also depends on the state of the connection
            UnsignedShortInteger::internal(0), // class
            UnsignedShortInteger::internal(0), // method
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function closeOk(FrameChannel $channel): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::channelCloseOk,
        ));
    }
}
