<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Model\Basic\Message,
    Model\Basic\Message\ContentType,
    Model\Basic\Message\ContentEncoding,
    Model\Basic\Message\DeliveryMode,
    Model\Basic\Message\Priority,
    Model\Basic\Message\CorrelationId,
    Model\Basic\Message\ReplyTo,
    Model\Basic\Message\Id,
    Model\Basic\Message\Type,
    Model\Basic\Message\UserId,
    Model\Basic\Message\AppId,
    Transport\Connection as ConnectionInterface,
    Transport\Frame\Value,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Immutable\{
    Map,
    Str,
    Predicate\Instance,
    Sequence,
    Maybe,
};

final class MessageReader
{
    public function __invoke(ConnectionInterface $connection): Message
    {
        $header = $connection->wait();

        return $header
            ->values()
            ->first()
            ->keep(Instance::of(Value\UnsignedLongLongInteger::class))
            ->map(static fn($value) => $value->original())
            ->flatMap(fn($bodySize) => $this->readPayload($connection, $bodySize, Str::of('')))
            ->map(Message::of(...))
            ->flatMap(
                fn($message) => $header
                    ->values()
                    ->get(1)
                    ->keep(Instance::of(Value\UnsignedShortInteger::class))
                    ->map(static fn($value) => $value->original())
                    ->flatMap(fn($flagBits) => $this->addProperties(
                        $message,
                        $flagBits,
                        $header
                            ->values()
                            ->drop(2), // for bodySize and flagBits
                    )),
            )
            ->match(
                static fn($message) => $message,
                static fn() => throw new \LogicException,
            );
    }

    /**
     * @param Sequence<Value> $properties
     *
     * @return Maybe<Message>
     */
    private function addProperties(
        Message $message,
        int $flagBits,
        Sequence $properties,
    ): Maybe {
        /** @var Sequence<array{int, callable(Maybe<Value>, Message): Maybe<Message>}> */
        $toParse = Sequence::of(
            [
                15,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\ShortString::class))
                    ->map(static fn($value) => $value->original()->toString())
                    ->flatMap(ContentType::maybe(...))
                    ->map(static fn($type) => $message->withContentType($type)),
            ],
            [
                14,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\ShortString::class))
                    ->map(static fn($value) => $value->original()->toString())
                    ->flatMap(ContentEncoding::maybe(...))
                    ->map(static fn($encoding) => $message->withContentEncoding($encoding)),
            ],
            [
                13,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\Table::class))
                    ->map(static fn($value) => $value->original())
                    ->map(static fn($headers) => $headers->map(
                        static fn($_, $value): mixed => $value->original(),
                    ))
                    ->map(static fn($headers) => $message->withHeaders($headers)),
            ],
            [
                12,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\UnsignedOctet::class))
                    ->map(static fn($value) => $value->original())
                    ->map(static fn($mode) => match ($mode) {
                        DeliveryMode::persistent->toInt() => DeliveryMode::persistent,
                        default => DeliveryMode::nonPersistent,
                    })
                    ->map(static fn($mode) => $message->withDeliveryMode($mode)),
            ],
            [
                11,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\UnsignedOctet::class))
                    ->map(static fn($value) => $value->original())
                    ->flatMap(Priority::maybe(...))
                    ->map(static fn($priority) => $message->withPriority($priority)),
            ],
            [
                10,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\ShortString::class))
                    ->map(static fn($value) => $value->original()->toString())
                    ->map(CorrelationId::of(...))
                    ->map(static fn($id) => $message->withCorrelationId($id)),
            ],
            [
                9,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\ShortString::class))
                    ->map(static fn($value) => $value->original()->toString())
                    ->map(ReplyTo::of(...))
                    ->map(static fn($replyTo) => $message->withReplyTo($replyTo)),
            ],
            [
                8,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\ShortString::class))
                    ->map(static fn($value) => (int) $value->original()->toString())
                    ->flatMap(ElapsedPeriod::maybe(...))
                    ->map(static fn($expiration) => $message->withExpiration($expiration)),
            ],
            [
                7,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\ShortString::class))
                    ->map(static fn($value) => $value->original()->toString())
                    ->map(Id::of(...))
                    ->map(static fn($id) => $message->withId($id)),
            ],
            [
                6,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\Timestamp::class))
                    ->map(static fn($value) => $value->original())
                    ->map(static fn($timestamp) => $message->withTimestamp($timestamp)),
            ],
            [
                5,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\ShortString::class))
                    ->map(static fn($value) => $value->original()->toString())
                    ->map(Type::of(...))
                    ->map(static fn($type) => $message->withType($type)),
            ],
            [
                4,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\ShortString::class))
                    ->map(static fn($value) => $value->original()->toString())
                    ->map(UserId::of(...))
                    ->map(static fn($id) => $message->withUserId($id)),
            ],
            [
                3,
                static fn(Maybe $value, Message $message) => $value
                    ->keep(Instance::of(Value\ShortString::class))
                    ->map(static fn($value) => $value->original()->toString())
                    ->map(AppId::of(...))
                    ->map(static fn($id) => $message->withAppId($id)),
            ],
        );

        /**
         * @psalm-suppress MixedArrayAccess
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedMethodCall
         */
        return $toParse
            ->filter(static fn($pair) => (bool) ($flagBits & (1 << $pair[0])))
            ->map(static fn($pair) => $pair[1])
            ->reduce(
                Maybe::just([$properties, $message]),
                static fn(Maybe $state, $parse): Maybe => $state->flatMap(
                    static fn($state) => $parse($state[0]->first(), $state[1])
                        ->map(static fn($message) => [$state[0]->drop(1), $message]),
                ),
            )
            ->map(static fn($state) => $state[1]);
    }

    /**
     * @return Maybe<Str>
     */
    private function readPayload(
        ConnectionInterface $connection,
        int $bodySize,
        Str $payload,
    ): Maybe {
        if ($payload->length() === $bodySize) {
            return Maybe::just($payload);
        }

        return $connection
            ->wait()
            ->content()
            ->map(static fn($chunk) => $payload->append($chunk->toString()))
            ->flatMap(fn($payload) => $this->readPayload(
                $connection,
                $bodySize,
                $payload,
            ));
    }
}
