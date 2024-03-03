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
use Innmind\TimeContinuum\{
    Earth,
    ElapsedPeriod,
};
use Innmind\Filesystem\File\Content;
use Innmind\IO\IO;
use Innmind\Stream\{
    Capabilities,
    Bidirectional,
};
use Innmind\Immutable\{
    Str,
    Predicate\Instance,
    Sequence,
    Maybe,
    Either,
};

/**
 * @internal
 */
final class MessageReader
{
    private Capabilities $streams;

    private function __construct(Capabilities $streams)
    {
        $this->streams = $streams;
    }

    /**
     * @return Either<Failure, Message>
     */
    public function __invoke(Connection $connection): Either
    {
        return $connection
            ->wait()
            ->flatMap(fn($received) => $this->decode(
                $connection,
                $received->frame(),
            ));
    }

    public static function of(Capabilities $streams): self
    {
        return new self($streams);
    }

    /**
     * @return Either<Failure, Message>
     */
    private function decode(
        Connection $connection,
        Frame $header,
    ): Either {
        return $header
            ->values()
            ->first()
            ->keep(Instance::of(Value\UnsignedLongLongInteger::class))
            ->map(static fn($value) => $value->original())
            ->either()
            ->leftMap(static fn() => Failure::toReadMessage())
            ->flatMap(fn($bodySize) => $this->readMessage($connection, $bodySize))
            ->flatMap(
                fn($message) => $header
                    ->values()
                    ->get(1)
                    ->keep(Instance::of(Value\UnsignedShortInteger::class))
                    ->map(static fn($value) => $value->original())
                    ->either()
                    ->leftMap(static fn() => Failure::toReadMessage())
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
     * @return Either<Failure, Message>
     */
    private function addProperties(
        Message $message,
        int $flagBits,
        Sequence $properties,
    ): Either {
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
                    ->flatMap(Earth\ElapsedPeriod::maybe(...))
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
         * @var Either<Failure, Message>
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
            ->map(static fn($state) => $state[1])
            ->either()
            ->leftMap(static fn() => Failure::toReadMessage());
    }

    /**
     * @return Either<Failure, Message>
     */
    private function readMessage(
        Connection $connection,
        int $bodySize,
    ): Either {
        $walk = $bodySize !== 0;
        $stream = $this->streams->temporary()->new();
        $read = Either::right([$connection, $stream, 0]);

        while ($walk) {
            $read = $read->flatMap($this->readChunk(...));
            $walk = $read->match(
                static fn($read) => $read[2] !== $bodySize,
                static fn() => false, // because no content was found in the last frame or failed to write the chunk to the temp stream
            );
        }

        $io = IO::of(fn(?ElapsedPeriod $timeout) => match ($timeout) {
            null => $this->streams->watch()->waitForever(),
            default => $this->streams->watch()->timeoutAfter($timeout),
        });

        return $read->map(
            static fn($in) => Message::file(Content::io(
                $io->readable()->wrap($in[1]),
            )),
        );
    }

    /**
     * @param array{Connection, Bidirectional, int} $in
     *
     * @return Either<Failure, array{Connection, Bidirectional, int}>
     */
    private function readChunk(array $in): Either
    {
        [$connection, $stream, $read] = $in;

        return $connection
            ->wait()
            ->flatMap(
                fn($received) => $this
                    ->accumulateChunk($received->frame(), $stream, $read)
                    ->map(static function($in) use ($connection) {
                        [$stream, $read] = $in;

                        return [$connection, $stream, $read];
                    }),
            );
    }

    /**
     * @return Either<Failure, array{Bidirectional, int}>
     */
    private function accumulateChunk(
        Frame $frame,
        Bidirectional $stream,
        int $read,
    ): Either {
        /** @var Either<Failure, array{Bidirectional, int}> */
        return $frame
            ->content()
            ->either()
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->flatMap(
                static fn($chunk) => $stream
                    ->write($chunk)
                    ->map(static fn($stream) => [
                        $stream,
                        $read + $chunk->length(),
                    ]),
            )
            ->leftMap(static fn() => Failure::toReadMessage());
    }
}
