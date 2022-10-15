<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Connection;

use Innmind\AMQP\{
    Model\Basic\Message,
    Model\Basic\Message\Generic,
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
};

final class MessageReader
{
    public function __invoke(ConnectionInterface $connection): Message
    {
        $header = $connection->wait();
        /** @var Value\UnsignedLongLongInteger */
        $value = $header->values()->first()->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $bodySize = $value->original();
        /** @var Value\UnsignedShortInteger */
        $value = $header->values()->get(1)->match(
            static fn($value) => $value,
            static fn() => throw new \LogicException,
        );
        $flagBits = $value->original();
        $payload = Str::of('');

        while ($payload->length() !== $bodySize) {
            $value = $connection
                ->wait()
                ->content()
                ->match(
                    static fn($value) => $value,
                    static fn() => throw new \LogicException,
                );
            $payload = $payload->append(
                $value->toString(),
            );
        }

        $message = new Generic($payload);
        $properties = $header
            ->values()
            ->drop(2);

        if ($flagBits & (1 << 15)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\ShortString $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            [$topLevel, $subType] = \explode(
                '/',
                $value->original()->toString(),
            );
            $message = $message->withContentType(new ContentType(
                $topLevel,
                $subType,
            ));
        }

        if ($flagBits & (1 << 14)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\ShortString $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withContentEncoding(new ContentEncoding(
                $value->original()->toString(),
            ));
        }

        if ($flagBits & (1 << 13)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\Table $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $headers = $value
                ->original()
                ->map(static fn($_, $value): mixed => $value->original());
            $message = $message->withHeaders($headers);
        }

        if ($flagBits & (1 << 12)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\UnsignedOctet $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withDeliveryMode(
                $value->original() === DeliveryMode::persistent->toInt() ?
                    DeliveryMode::persistent : DeliveryMode::nonPersistent,
            );
        }

        if ($flagBits & (1 << 11)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\UnsignedOctet $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withPriority(Priority::of(
                $value->original(),
            ));
        }

        if ($flagBits & (1 << 10)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\ShortString $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withCorrelationId(new CorrelationId(
                $value->original()->toString(),
            ));
        }

        if ($flagBits & (1 << 9)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\ShortString $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withReplyTo(new ReplyTo(
                $value->original()->toString(),
            ));
        }

        if ($flagBits & (1 << 8)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\ShortString $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withExpiration(new ElapsedPeriod(
                (int) $value->original()->toString(),
            ));
        }

        if ($flagBits & (1 << 7)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\ShortString $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withId(new Id(
                $value->original()->toString(),
            ));
        }

        if ($flagBits & (1 << 6)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\Timestamp $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withTimestamp(
                $value->original(),
            );
        }

        if ($flagBits & (1 << 5)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\ShortString $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withType(new Type(
                $value->original()->toString(),
            ));
        }

        if ($flagBits & (1 << 4)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\ShortString $value
             */
            [$value, $properties] = $properties->match(
                static fn($value, $properties) => [$value, $properties],
                static fn() => throw new \LogicException,
            );
            $message = $message->withUserId(new UserId(
                $value->original()->toString(),
            ));
        }

        if ($flagBits & (1 << 3)) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var Value\ShortString $value
             */
            $value = $properties->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException,
            );
            $message = $message->withAppId(new AppId(
                $value->original()->toString(),
            ));
        }

        return $message;
    }
}
