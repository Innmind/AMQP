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
    Transport\Connection,
    Transport\Frame\Value,
    Predicate\IsInt,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Filesystem\File\Content;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Map,
    Str,
    Predicate\Instance,
    Sequence,
    Maybe,
};

final class MessageReader
{
    public function __invoke(Connection $connection): Message
    {
        $received = $connection->wait()->match(
            static fn($received) => $received,
            static fn() => throw new \RuntimeException,
        );
        $connection = $received->connection();
        $header = $received->frame();

        return $header
            ->values()
            ->first()
            ->keep(Instance::of(Value\UnsignedLongLongInteger::class))
            ->map(static fn($value) => $value->original())
            ->flatMap(fn($bodySize) => $this->readPayload($connection, $bodySize))
            ->map(Message::file(...))
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
     * @return Maybe<Content>
     */
    private function readPayload(
        Connection $connection,
        int $bodySize,
    ): Maybe {
        $walk = $bodySize !== 0;
        $read = Maybe::just(0);
        $stream = \fopen('php://temp', 'r+');

        while ($walk) {
            $read = $connection
                ->wait()
                ->map(static fn($received) => $received->frame())
                ->match(
                    static fn($frame) => $frame,
                    static fn() => throw new \RuntimeException,
                )
                ->content()
                ->map(static fn($chunk) => \fwrite($stream, $chunk->toString()))
                ->keep(IsInt::natural())
                ->flatMap(static fn(int $written) => $read->map(
                    static fn(int $read) => $written + $read,
                ));
            $walk = $read->match(
                static fn($read) => $read !== $bodySize,
                static fn() => false, // because no content was found in the last frame or failed to write the chunk to the temp stream
            );
        }

        /** @var Maybe<Content> */
        return $read->map(static fn() => Content\OfStream::of(Readable\Stream::of($stream)));
    }
}
