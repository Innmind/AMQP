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
    MapInterface,
    Map,
    StreamInterface,
    Stream,
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
        return Frame::method(
            $channel,
            Methods::get('basic.ack'),
            new UnsignedLongLongInteger(new Integer($command->deliveryTag())),
            new Bits($command->isMultiple())
        );
    }

    public function cancel(FrameChannel $channel, Cancel $command): Frame
    {
        return Frame::method(
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

        return Frame::method(
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
            $this->arguments($command->arguments())
        );
    }

    public function get(FrameChannel $channel, Get $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.get'),
            new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
            new ShortString(new Str($command->queue())),
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
    ): StreamInterface {
        $frames = Stream::of(
            Frame::class,
            Frame::method(
                $channel,
                Methods::get('basic.publish'),
                new UnsignedShortInteger(new Integer(0)), //ticket (reserved)
                new ShortString(new Str($command->exchange())),
                new ShortString(new Str($command->routingKey())),
                new Bits(
                    $command->mandatory(),
                    $command->immediate()
                )
            ),
            Frame::header(
                $channel,
                Methods::classId('basic'),
                new UnsignedLongLongInteger(new Integer(
                    $command->message()->body()->length()
                )),
                ...$this->serializeProperties($command->message())
            )
        );

        //the "-8" is due to the content frame extra informations (type, channel and end flag)
        $chunk = $maxFrameSize->isLimited() ? ($maxFrameSize->toInt() - 8) : $command->message()->body()->length();

        return $command
            ->message()
            ->body()
            ->chunk($chunk)
            ->reduce(
                $frames,
                static function(Stream $frames, Str $chunk) use ($channel): Stream {
                    return $frames->add(Frame::body($channel, $chunk));
                }
            );
    }

    public function qos(FrameChannel $channel, Qos $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.qos'),
            new UnsignedLongInteger(new Integer($command->prefetchSize())),
            new UnsignedShortInteger(new Integer($command->prefetchCount())),
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
            new UnsignedLongLongInteger(new Integer($command->deliveryTag())),
            new Bits($command->shouldRequeue())
        );
    }

    private function arguments(MapInterface $arguments): Table
    {
        return new Table(
            $arguments->reduce(
                new Map('string', Value::class),
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
            $properties[] = new ShortString(
                new Str((string) $message->contentType())
            );
            $flagBits |= (1 << 15);
        }

        if ($message->hasContentEncoding()) {
            $properties[] = new ShortString(
                new Str((string) $message->contentEncoding())
            );
            $flagBits |= (1 << 14);
        }

        if ($message->hasHeaders()) {
            $properties[] = $this->arguments($message->headers());
            $flagBits |= (1 << 13);
        }

        if ($message->hasDeliveryMode()) {
            $properties[] = new UnsignedOctet(
                new Integer($message->deliveryMode()->toInt())
            );
            $flagBits |= (1 << 12);
        }

        if ($message->hasPriority()) {
            $properties[] = new UnsignedOctet(
                new Integer($message->priority()->toInt())
            );
            $flagBits |= (1 << 11);
        }

        if ($message->hasCorrelationId()) {
            $properties[] = new ShortString(
                new Str((string) $message->correlationId())
            );
            $flagBits |= (1 << 10);
        }

        if ($message->hasReplyTo()) {
            $properties[] = new ShortString(
                new Str((string) $message->replyTo())
            );
            $flagBits |= (1 << 9);
        }

        if ($message->hasExpiration()) {
            $properties[] = new ShortString(
                new Str((string) $message->expiration()->milliseconds())
            );
            $flagBits |= (1 << 8);
        }

        if ($message->hasId()) {
            $properties[] = new ShortString(
                new Str((string) $message->id())
            );
            $flagBits |= (1 << 7);
        }

        if ($message->hasTimestamp()) {
            $properties[] = new Timestamp($message->timestamp());
            $flagBits |= (1 << 6);
        }

        if ($message->hasType()) {
            $properties[] = new ShortString(
                new Str((string) $message->type())
            );
            $flagBits |= (1 << 5);
        }

        if ($message->hasUserId()) {
            $properties[] = new ShortString(
                new Str((string) $message->userId())
            );
            $flagBits |= (1 << 4);
        }

        if ($message->hasAppId()) {
            $properties[] = new ShortString(
                new Str((string) $message->appId())
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
