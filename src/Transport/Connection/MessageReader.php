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
        $value = $header->values()->first();
        $bodySize = $value
            ->original()
            ->value();
        /** @var Value\UnsignedShortInteger */
        $value = $header->values()->get(1);
        $flagBits = $value
            ->original()
            ->value();
        $payload = Str::of('');

        while ($payload->length() !== $bodySize) {
            /** @var Value\Text */
            $value = $connection
                ->wait()
                ->values()
                ->first();
            $payload = $payload->append(
                $value
                    ->original()
                    ->toString(),
            );
        }

        $message = new Generic($payload);
        $properties = $header
            ->values()
            ->drop(2);

        if ($flagBits & (1 << 15)) {
            /** @var Value\ShortString */
            $value = $properties->first();
            [$topLevel, $subType] = \explode(
                '/',
                $value->original()->toString(),
            );
            $message = $message->withContentType(new ContentType(
                $topLevel,
                $subType,
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 14)) {
            /** @var Value\ShortString */
            $value = $properties->first();
            $message = $message->withContentEncoding(new ContentEncoding(
                $value->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 13)) {
            /** @var Value\Table */
            $value = $properties->first();
            $headers = $value
                ->original()
                ->toMapOf(
                    'string',
                    'mixed',
                    static function(string $key, Value $value): \Generator {
                        yield $key => $value->original();
                    },
                );
            $message = $message->withHeaders($headers);
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 12)) {
            /** @var Value\UnsignedOctet */
            $value = $properties->first();
            $message = $message->withDeliveryMode(
                $value->original()->value() === DeliveryMode::persistent()->toInt() ?
                    DeliveryMode::persistent() : DeliveryMode::nonPersistent(),
            );
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 11)) {
            /** @var Value\UnsignedOctet */
            $value = $properties->first();
            $message = $message->withPriority(new Priority(
                $value->original()->value(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 10)) {
            /** @var Value\ShortString */
            $value = $properties->first();
            $message = $message->withCorrelationId(new CorrelationId(
                $value->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 9)) {
            /** @var Value\ShortString */
            $value = $properties->first();
            $message = $message->withReplyTo(new ReplyTo(
                $value->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 8)) {
            /** @var Value\ShortString */
            $value = $properties->first();
            $message = $message->withExpiration(new ElapsedPeriod(
                (int) $value->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 7)) {
            /** @var Value\ShortString */
            $value = $properties->first();
            $message = $message->withId(new Id(
                $value->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 6)) {
            /** @var Value\Timestamp */
            $value = $properties->first();
            $message = $message->withTimestamp(
                $value->original(),
            );
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 5)) {
            /** @var Value\ShortString */
            $value = $properties->first();
            $message = $message->withType(new Type(
                $value->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 4)) {
            /** @var Value\ShortString */
            $value = $properties->first();
            $message = $message->withUserId(new UserId(
                $value->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 3)) {
            /** @var Value\ShortString */
            $value = $properties->first();
            $message = $message->withAppId(new AppId(
                $value->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        return $message;
    }
}
