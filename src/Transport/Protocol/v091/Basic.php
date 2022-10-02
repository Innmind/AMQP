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
            UnsignedLongLongInteger::of(Integer::of($command->deliveryTag())),
            new Bits($command->isMultiple()),
        );
    }

    public function cancel(FrameChannel $channel, Cancel $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.cancel'),
            ShortString::of(Str::of($command->consumerTag())),
            new Bits(!$command->shouldWait()),
        );
    }

    public function consume(FrameChannel $channel, Consume $command): Frame
    {
        $consumerTag = $command->consumerTag()->match(
            static fn($tag) => $tag,
            static fn() => '',
        );

        return Frame::method(
            $channel,
            Methods::get('basic.consume'),
            new UnsignedShortInteger(Integer::of(0)), // ticket (reserved)
            ShortString::of(Str::of($command->queue())),
            ShortString::of(Str::of($consumerTag)),
            new Bits(
                !$command->isLocal(),
                $command->shouldAutoAcknowledge(),
                $command->isExclusive(),
                !$command->shouldWait(),
            ),
            $this->arguments($command->arguments()),
        );
    }

    public function get(FrameChannel $channel, Get $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.get'),
            new UnsignedShortInteger(Integer::of(0)), // ticket (reserved)
            ShortString::of(Str::of($command->queue())),
            new Bits($command->shouldAutoAcknowledge()),
        );
    }

    public function publish(
        FrameChannel $channel,
        Publish $command,
        MaxFrameSize $maxFrameSize,
    ): Sequence {
        $frames = Sequence::of(
            Frame::method(
                $channel,
                Methods::get('basic.publish'),
                new UnsignedShortInteger(Integer::of(0)), // ticket (reserved)
                ShortString::of(Str::of($command->exchange())),
                ShortString::of(Str::of($command->routingKey())),
                new Bits(
                    $command->mandatory(),
                    $command->immediate(),
                ),
            ),
            Frame::header(
                $channel,
                Methods::classId('basic'),
                UnsignedLongLongInteger::of(Integer::of(
                    $command->message()->body()->length(),
                )),
                ...$this->serializeProperties($command->message()),
            ),
        );

        // the "-8" is due to the content frame extra informations (type, channel and end flag)
        $chunk = $maxFrameSize->isLimited() ? ($maxFrameSize->toInt() - 8) : $command->message()->body()->length();

        if ($chunk === 0) {
            return $frames;
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $payloadFrames = $command
            ->message()
            ->body()
            ->chunk($chunk)
            ->map(static fn($chunk) => Frame::body($channel, $chunk));

        return $frames->append($payloadFrames);
    }

    public function qos(FrameChannel $channel, Qos $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.qos'),
            UnsignedLongInteger::of(Integer::of($command->prefetchSize())),
            UnsignedShortInteger::of(Integer::of($command->prefetchCount())),
            new Bits($command->isGlobal()),
        );
    }

    public function recover(FrameChannel $channel, Recover $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.recover'),
            new Bits($command->shouldRequeue()),
        );
    }

    public function reject(FrameChannel $channel, Reject $command): Frame
    {
        return Frame::method(
            $channel,
            Methods::get('basic.reject'),
            UnsignedLongLongInteger::of(Integer::of($command->deliveryTag())),
            new Bits($command->shouldRequeue()),
        );
    }

    /**
     * @param Map<string, mixed> $arguments
     */
    private function arguments(Map $arguments): Table
    {
        return new Table($arguments->map(
            fn($_, $value) => ($this->translate)($value),
        ));
    }

    /**
     * @return list<Value>
     */
    private function serializeProperties(Message $message): array
    {
        /** @var Sequence<Value> */
        $properties = Sequence::of();
        $flagBits = 0;

        [$flagBits, $properties] = $message->contentType()->match(
            static fn($contentType) => [
                $flagBits | (1 << 15),
                ($properties)(ShortString::of(Str::of($contentType->toString()))),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->contentEncoding()->match(
            static fn($contentEncoding) => [
                $flagBits | (1 << 14),
                ($properties)(ShortString::of(Str::of($contentEncoding->toString()))),
            ],
            static fn() => [$flagBits, $properties],
        );

        if (!$message->headers()->empty()) {
            $properties = ($properties)($this->arguments($message->headers()));
            $flagBits |= (1 << 13);
        }

        [$flagBits, $properties] = $message->deliveryMode()->match(
            static fn($deliveryMode) => [
                $flagBits | (1 << 12),
                ($properties)(UnsignedOctet::of(Integer::of($deliveryMode->toInt()))),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->priority()->match(
            static fn($priority) => [
                $flagBits | (1 << 11),
                ($properties)(UnsignedOctet::of(Integer::of($priority->toInt()))),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->correlationId()->match(
            static fn($correlationId) => [
                $flagBits | (1 << 10),
                ($properties)(ShortString::of(Str::of($correlationId->toString()))),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->replyTo()->match(
            static fn($replyTo) => [
                $flagBits | (1 << 9),
                ($properties)(ShortString::of(Str::of($replyTo->toString()))),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->expiration()->match(
            static fn($expiration) => [
                $flagBits | (1 << 8),
                ($properties)(ShortString::of(Str::of((string) $expiration->milliseconds()))),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->id()->match(
            static fn($id) => [
                $flagBits | (1 << 7),
                ($properties)(ShortString::of(Str::of($id->toString()))),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->timestamp()->match(
            static fn($timestamp) => [
                $flagBits | (1 << 6),
                ($properties)(new Timestamp($timestamp)),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->type()->match(
            static fn($type) => [
                $flagBits | (1 << 5),
                ($properties)(ShortString::of(Str::of($type->toString()))),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->userId()->match(
            static fn($userId) => [
                $flagBits | (1 << 4),
                ($properties)(ShortString::of(Str::of($userId->toString()))),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->appId()->match(
            static fn($appId) => [
                $flagBits | (1 << 3),
                ($properties)(ShortString::of(Str::of($appId->toString()))),
            ],
            static fn() => [$flagBits, $properties],
        );

        return [
            new UnsignedShortInteger(Integer::of($flagBits)),
            ...$properties->toList(),
        ];
    }
}
