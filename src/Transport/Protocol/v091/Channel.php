<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

use Innmind\AMQP\{
    Model\Channel\Flow,
    Model\Channel\FlowOk,
    Model\Channel\Close,
    Transport\Protocol\Channel as ChannelInterface,
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

final class Channel implements ChannelInterface
{
    public function open(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('channel.open'),
            new ShortString(new Str('')) //out of band (reserved)
        );
    }

    public function flow(FrameChannel $channel, Flow $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('channel.flow'),
            new Bits($command->active())
        );
    }

    public function flowOk(FrameChannel $channel, FlowOk $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('channel.flow-ok'),
            new Bits($command->active())
        );
    }

    public function close(FrameChannel $channel, Close $command): Frame
    {
        $replyCode = 0;
        $replyText = '';
        $method = new Method(0, 0);

        if ($command->hasReply()) {
            $replyCode = $command->replyCode();
            $replyText = $command->replyText();
        }

        if ($command->causedKnown()) {
            $method = Methods::get($command->cause());
        }

        return Frame::method(
            $channel,
            Methods::get('channel.close'),
            new UnsignedShortInteger(new Integer($replyCode)),
            new ShortString(new Str($replyText)),
            new UnsignedShortInteger(new Integer($method->class())),
            new UnsignedShortInteger(new Integer($method->method()))
        );
    }

    public function closeOk(FrameChannel $channel): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('channel.close-ok')
        );
    }
}
