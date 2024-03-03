<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

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
    Transport\Frame\Channel as FrameChannel,
    Transport\Frame\Method,
    Transport\Frame\MethodClass,
    Transport\Frame\Value,
    Transport\Frame\Value\UnsignedLongLongInteger,
    Transport\Frame\Value\UnsignedLongInteger,
    Transport\Frame\Value\Bits,
    Transport\Frame\Value\ShortString,
    Transport\Frame\Value\UnsignedShortInteger,
    Transport\Frame\Value\Table,
    Transport\Frame\Value\UnsignedOctet,
    Transport\Frame\Value\Timestamp,
};
use Innmind\Immutable\{
    Str,
    Map,
    Sequence,
};

/**
 * @internal
 */
final class Basic
{
    private ArgumentTranslator $translate;

    public function __construct(ArgumentTranslator $translator)
    {
        $this->translate = $translator;
    }

    /**
     * @return Sequence<Frame>
     */
    public function ack(FrameChannel $channel, Ack $command): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::basicAck,
            UnsignedLongLongInteger::internal($command->deliveryTag()),
            Bits::of($command->isMultiple()),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function cancel(FrameChannel $channel, Cancel $command): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::basicCancel,
            ShortString::of(Str::of($command->consumerTag())),
            Bits::of(!$command->shouldWait()),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function consume(FrameChannel $channel, Consume $command): Sequence
    {
        $consumerTag = $command->consumerTag()->match(
            static fn($tag) => $tag,
            static fn() => '',
        );

        return Sequence::of(Frame::method(
            $channel,
            Method::basicConsume,
            UnsignedShortInteger::internal(0), // ticket (reserved)
            ShortString::of(Str::of($command->queue())),
            ShortString::of(Str::of($consumerTag)),
            Bits::of(
                !$command->isLocal(),
                $command->shouldAutoAcknowledge(),
                $command->isExclusive(),
                !$command->shouldWait(),
            ),
            $this->arguments($command->arguments()),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function get(FrameChannel $channel, Get $command): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::basicGet,
            UnsignedShortInteger::internal(0), // ticket (reserved)
            ShortString::of(Str::of($command->queue())),
            Bits::of($command->shouldAutoAcknowledge()),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function publish(
        FrameChannel $channel,
        Publish $command,
        MaxFrameSize $maxFrameSize,
    ): Sequence {
        // we use a lazy sequence to allow streaming frames for messages having
        // a lazy sequence of chunks for a body
        $frames = Sequence::lazyStartingWith(
            Frame::method(
                $channel,
                Method::basicPublish,
                UnsignedShortInteger::internal(0), // ticket (reserved)
                ShortString::of(Str::of($command->exchange())),
                ShortString::of(Str::of($command->routingKey())),
                Bits::of(
                    $command->mandatory(),
                    $command->immediate(),
                ),
            ),
            Frame::header(
                $channel,
                MethodClass::basic,
                UnsignedLongLongInteger::of($command->message()->length()),
                ...$this->serializeProperties($command->message()),
            ),
        );

        return $frames->append(
            $maxFrameSize
                ->chunk($command->message())
                ->map(static fn($chunk) => Frame::body($channel, $chunk)),
        );
    }

    /**
     * @return Sequence<Frame>
     */
    public function qos(FrameChannel $channel, Qos $command): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::basicQos,
            UnsignedLongInteger::internal($command->prefetchSize()),
            UnsignedShortInteger::internal($command->prefetchCount()),
            Bits::of($command->isGlobal()),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function recover(FrameChannel $channel, Recover $command): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::basicRecover,
            Bits::of($command->shouldRequeue()),
        ));
    }

    /**
     * @return Sequence<Frame>
     */
    public function reject(FrameChannel $channel, Reject $command): Sequence
    {
        return Sequence::of(Frame::method(
            $channel,
            Method::basicReject,
            UnsignedLongLongInteger::internal($command->deliveryTag()),
            Bits::of($command->shouldRequeue()),
        ));
    }

    /**
     * @param Map<string, mixed> $arguments
     */
    private function arguments(Map $arguments): Table
    {
        return Table::of($arguments->map(
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
        /** @var int<0, 65535> */
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
                ($properties)(UnsignedOctet::of($deliveryMode->toInt())),
            ],
            static fn() => [$flagBits, $properties],
        );
        [$flagBits, $properties] = $message->priority()->match(
            static fn($priority) => [
                $flagBits | (1 << 11),
                ($properties)(UnsignedOctet::of($priority->toInt())),
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
                ($properties)(Timestamp::of($timestamp)),
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

        /** @psalm-suppress ArgumentTypeCoercion */
        return [
            UnsignedShortInteger::internal($flagBits),
            ...$properties->toList(),
        ];
    }
}
