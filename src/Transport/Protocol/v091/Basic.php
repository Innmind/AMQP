<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol\v091;

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
    Transport\Frame\Type,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\Table,
    Transport\Protocol\Basic as BasicInterface,
    Transport\Protocol\ArgumentTranslator
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    Map
};

final class Basic implements BasicInterface
{
    private $translate;

    public function __construct(ArgumentTranslator $translator)
    {
        $this->translate = $translator;
    }

    public function ack(FrameChannel $channel, Ack $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('basic.ack'),
            new UnsignedLongLongInteger(new Integer($command->deliveryTag())),
            new Bits($command->isMultiple())
        );
    }

    public function cancel(FrameChannel $channel, Cancel $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('basic.cancel'),
            new ShortString(new Str($command->consumerTag())),
            new Bits(!$command->shouldWait())
        );
    }

    public function consume(FrameChannel $channel, Consume $command): Frame
    {
        $consumerTag = '';

        if (!$command->shouldAutoGenerateConsumerTag()) {
            $consumerTag = $command->consumerTag();
        }

        return Frame::command(
            $channel,
            Methods::get('basic.consume'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->queue())),
            new ShortString(new Str($consumerTag)),
            new Bits(
                !$command->isLocal(),
                $command->shouldAutoAcknowledge(),
                $command->isExclusive(),
                !$command->shouldWait()
            ),
            new Table(
                $command
                    ->arguments()
                    ->reduce(
                        new Map('string', Value::class),
                        function(Map $carry, string $key, $value): Map {
                            return $carry->put(
                                $key,
                                ($this->translate)($value)
                            );
                        }
                    )
            )
        );
    }

    public function get(FrameChannel $channel, Get $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('basic.get'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->queue())),
            new Bits($command->shouldAutoAcknowledge())
        );
    }

    public function publish(FrameChannel $channel, Publish $command): Frame
    {
        return new Frame(
            Type::method(),
            $channel,
            Methods::get('basic.publish'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->exchange())),
            new ShortString(new Str($command->routingKey())),
            new Bits(
                $command->mandatory(),
                $command->immediate()
            )
        );
    }

    public function qos(FrameChannel $channel, Qos $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('basic.qos'),
            new UnsignedLongInteger(new Integer($command->prefetchSize())),
            new UnsignedShortInteger(new Integer($command->prefetchCount())),
            new Bits($command->isGlobal())
        );
    }

    public function recover(FrameChannel $channel, Recover $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('basic.recover'),
            new Bits($command->shouldRequeue())
        );
    }

    public function reject(FrameChannel $channel, Reject $command): Frame
    {
        return Frame::command(
            $channel,
            Methods::get('basic.reject'),
            new UnsignedLongLongInteger(new Integer($command->deliveryTag())),
            new Bits($command->shouldRequeue())
        );
    }
}
