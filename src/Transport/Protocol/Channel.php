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
    Transport\Frame\Type,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\UnsignedShortInteger,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\Str;

final class Channel
{
    public function open(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Method::channelOpen,
            new ShortString(Str::of('')), // out of band (reserved)
        );
    }

    public function flow(FrameChannel $channel, Flow $command): Frame
    {
        return Frame::method(
            $channel,
            Method::channelFlow,
            new Bits($command->active()),
        );
    }

    public function flowOk(FrameChannel $channel, FlowOk $command): Frame
    {
        return Frame::method(
            $channel,
            Method::channelFlowOk,
            new Bits($command->active()),
        );
    }

    public function close(FrameChannel $channel, Close $command): Frame
    {
        [$replyCode, $replyText] = $command->response()->match(
            static fn($info) => $info,
            static fn() => [0, ''],
        );

        [$class, $method] = $command->cause()->match(
            static fn($cause) => [
                Method::of($cause)->class()->toInt(),
                Method::of($cause)->method(),
            ],
            static fn() => [0, 0],
        );

        return Frame::method(
            $channel,
            Method::channelClose,
            UnsignedShortInteger::of(Integer::of($replyCode)),
            ShortString::of(Str::of($replyText)),
            UnsignedShortInteger::of(Integer::of($class)),
            UnsignedShortInteger::of(Integer::of($method)),
        );
    }

    public function closeOk(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Method::channelCloseOk,
        );
    }
}
