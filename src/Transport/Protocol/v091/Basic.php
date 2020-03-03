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
    Model\Basic\Message,
    Model\Connection\MaxFrameSize,
    Transport\Frame,
    Transport\Frame\Type,
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Method,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\Timestamp,
    Transport\Protocol\Basic as BasicInterface,
    Transport\Protocol\ArgumentTranslator,
};
use Innmind\Math\Algebra\Integer;
use Innmind\Immutable\{
    Str,
    Map,
    Sequence,
};

final class Basic implements BasicInterface
{
    private ArgumentTranslator $translate;

    public function __construct(ArgumentTranslator $translator)
    {
        $this->translate = $translator;
    }

    public function ack(FrameChannel $channel, Ack $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.ack'),
            UnsignedLongLongInteger::of(new Integer($command->deliveryTag())),
            new Bits($command->isMultiple())
        );
    }

    public function cancel(FrameChannel $channel, Cancel $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.cancel'),
            ShortString::of(Str::of($command->consumerTag())),
            new Bits(!$command->shouldWait())
        );
    }

    public function consume(FrameChannel $channel, Consume $command): Frame
    {
        $consumerTag = '';

        if (!$command->shouldAutoGenerateConsumerTag()) {
            $consumerTag = $command->consumerTag();
        }

        return Frame::method(
            $channel,
            Methods::get('basic.consume'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            ShortString::of(Str::of($command->queue())),
            ShortString::of(Str::of($consumerTag)),
            new Bits(
                !$command->isLocal(),
                $command->shouldAutoAcknowledge(),
                $command->isExclusive(),
                !$command->shouldWait()
            ),
            $this->arguments($command->arguments())
        );
    }

    public function get(FrameChannel $channel, Get $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.get'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            ShortString::of(Str::of($command->queue())),
            new Bits($command->shouldAutoAcknowledge())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function publish(
        FrameChannel $channel,
        Publish $command,
        MaxFrameSize $maxFrameSize
    ): Sequence {
        $frames = Sequence::of(
            Frame::class,
            Frame::method(
                $channel,
                Methods::get('basic.publish'),
                new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
                ShortString::of(Str::of($command->exchange())),
                ShortString::of(Str::of($command->routingKey())),
                new Bits(
                    $command->mandatory(),
                    $command->immediate()
                )
            ),
            Frame::header(
                $channel,
                Methods::classId('basic'),
                UnsignedLongLongInteger::of(new Integer(
                    $command->message()->body()->length()
                )),
                ...$this->serializeProperties($command->message())
            )
        );

        //the "-8" is due to the content frame extra informations (type, channel and end flag)
        $chunk = $maxFrameSize->isLimited() ? ($maxFrameSize->toInt() - 8) : $command->message()->body()->length();

        if ($chunk === 0) {
            return $frames;
        }

        return $command
            ->message()
            ->body()
            ->chunk($chunk)
            ->reduce(
                $frames,
                static function(Sequence $frames, Str $chunk) use ($channel): Sequence {
                    return $frames->add(Frame::body($channel, $chunk));
                }
            );
    }

    public function qos(FrameChannel $channel, Qos $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.qos'),
            UnsignedLongInteger::of(new Integer($command->prefetchSize())),
            UnsignedShortInteger::of(new Integer($command->prefetchCount())),
            new Bits($command->isGlobal())
        );
    }

    public function recover(FrameChannel $channel, Recover $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.recover'),
            new Bits($command->shouldRequeue())
        );
    }

    public function reject(FrameChannel $channel, Reject $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.reject'),
            UnsignedLongLongInteger::of(new Integer($command->deliveryTag())),
            new Bits($command->shouldRequeue())
        );
    }

    private function arguments(Map $arguments): Table
    {
        return new Table(
            $arguments->reduce(
                Map::of('string', Value::class),
                function(Map $carry, string $key, $value): Map {
                    return $carry->put(
                        $key,
                        ($this->translate)($value)
                    );
                }
            )
        );
    }

    private function serializeProperties(Message $message): array
    {
        $properties = [];
        $flagBits = 0;

        if ($message->hasContentType()) {
            $properties[] = ShortString::of(
                Str::of((string) $message->contentType())
            );
            $flagBits |= (1 << 15);
        }

        if ($message->hasContentEncoding()) {
            $properties[] = ShortString::of(
                Str::of((string) $message->contentEncoding())
            );
            $flagBits |= (1 << 14);
        }

        if ($message->hasHeaders()) {
            $properties[] = $this->arguments($message->headers());
            $flagBits |= (1 << 13);
        }

        if ($message->hasDeliveryMode()) {
            $properties[] = UnsignedOctet::of(
                new Integer($message->deliveryMode()->toInt())
            );
            $flagBits |= (1 << 12);
        }

        if ($message->hasPriority()) {
            $properties[] = UnsignedOctet::of(
                new Integer($message->priority()->toInt())
            );
            $flagBits |= (1 << 11);
        }

        if ($message->hasCorrelationId()) {
            $properties[] = ShortString::of(
                Str::of((string) $message->correlationId())
            );
            $flagBits |= (1 << 10);
        }

        if ($message->hasReplyTo()) {
            $properties[] = ShortString::of(
                Str::of((string) $message->replyTo())
            );
            $flagBits |= (1 << 9);
        }

        if ($message->hasExpiration()) {
            $properties[] = ShortString::of(
                Str::of((string) $message->expiration()->milliseconds())
            );
            $flagBits |= (1 << 8);
        }

        if ($message->hasId()) {
            $properties[] = ShortString::of(
                Str::of((string) $message->id())
            );
            $flagBits |= (1 << 7);
        }

        if ($message->hasTimestamp()) {
            $properties[] = new Timestamp($message->timestamp());
            $flagBits |= (1 << 6);
        }

        if ($message->hasType()) {
            $properties[] = ShortString::of(
                Str::of((string) $message->type())
            );
            $flagBits |= (1 << 5);
        }

        if ($message->hasUserId()) {
            $properties[] = ShortString::of(
                Str::of((string) $message->userId())
            );
            $flagBits |= (1 << 4);
        }

        if ($message->hasAppId()) {
            $properties[] = ShortString::of(
                Str::of((string) $message->appId())
            );
            $flagBits |= (1 << 3);
        }

        \array_unshift(
            $properties,
            new UnsignedShortInteger(new Integer($flagBits))
        );

        return $properties;
    }
}
