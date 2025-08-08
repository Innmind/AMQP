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
    Transport\Frame,
    Transport\Frame\Value,
    Failure,
};
use Innmind\OperatingSystem\Filesystem;
use Innmind\TimeContinuum\Period;
use Innmind\Filesystem\File\Content;
use Innmind\IO\Stream\Size\Unit;
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Str,
    Predicate\Instance,
    Sequence,
    Maybe,
    Attempt,
};

/**
 * @internal
 */
final class MessageReader
{
    private function __construct(private Filesystem $filesystem)
    {
    }

    /**
     * @return Attempt<Message>
     */
    public function __invoke(Connection $connection): Attempt
    {
        return $connection
            ->wait()
            ->flatMap(fn($received) => $this->decode(
                $connection,
                $received->frame(),
            ));
    }

    public static function of(Filesystem $filesystem): self
    {
        return new self($filesystem);
    }

    /**
     * @return Attempt<Message>
     */
    private function decode(
        Connection $connection,
        Frame $header,
    ): Attempt {
        return $header
            ->values()
            ->first()
            ->keep(Instance::of(Value\UnsignedLongLongInteger::class))
            ->map(static fn($value) => $value->original())
            ->attempt(static fn() => Failure::toReadMessage())
            ->flatMap(fn($bodySize) => $this->readMessage($connection, $bodySize))
            ->flatMap(
                fn($message) => $header
                    ->values()
                    ->get(1)
                    ->keep(Instance::of(Value\UnsignedShortInteger::class))
                    ->map(static fn($value) => $value->original())
                    ->attempt(static fn() => Failure::toReadMessage())
                    ->flatMap(fn($flagBits) => $this->addProperties(
                        $message,
                        $flagBits,
                        $header
                            ->values()
                            ->drop(2), // for bodySize and flagBits
                    )),
            );
    }

    /**
     * @param Sequence<Value> $properties
     *
     * @return Attempt<Message>
     */
    private function addProperties(
        Message $message,
        int $flagBits,
        Sequence $properties,
    ): Attempt {
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
                    ->keep(
                        Is::int()
                            ->positive()
                            ->or(Is::value(0))
                            ->asPredicate(),
                    )
                    ->map(Period::millisecond(...))
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
            ->sink([$properties, $message])
            ->maybe(
                static fn($state, $parse) => $parse($state[0]->first(), $state[1])
                    ->map(static fn($message) => [$state[0]->drop(1), $message]),
            )
            ->map(static fn($state) => $state[1])
            ->attempt(static fn() => Failure::toReadMessage());
    }

    /**
     * @return Attempt<Message>
     */
    private function readMessage(
        Connection $connection,
        int $bodySize,
    ): Attempt {
        $chunks = Sequence::lazy(static function() use ($connection, $bodySize) {
            $continue = $bodySize !== 0;
            $read = 0;

            while ($continue) {
                $chunk = $connection
                    ->wait()
                    ->maybe()
                    ->flatMap(static fn($received) => $received->frame()->content())
                    ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
                    ->attempt(static fn() => new \RuntimeException('Failed to read chunk'));
                $read += $chunk->match(
                    static fn($chunk) => $chunk->length(),
                    static fn() => 0,
                );
                $continue = $chunk->match(
                    static fn() => $read !== $bodySize,
                    static fn() => false,
                );

                yield $chunk;
            }
        });

        /** @var Sequence<Str> */
        $unfolded = Sequence::of();
        $content = match (true) {
            $bodySize <= Unit::megabytes->times(2) => $chunks
                ->sink($unfolded)
                ->attempt(static fn($chunks, $chunk) => $chunk->map($chunks))
                ->map(Content::ofChunks(...)),
            default => $this
                ->filesystem
                ->temporary($chunks)
                ->memoize(), // to prevent using a deferred Attempt that would result in out of order reading the socket
        };

        return $content
            ->map(Message::file(...))
            ->mapError(Failure::as(Failure::toReadMessage()));
    }
}
