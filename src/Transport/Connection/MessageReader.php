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
        $bodySize = $header
            ->values()
            ->first()
            ->original()
            ->value();
        $flagBits = $header
            ->values()
            ->get(1)
            ->original()
            ->value();
        $payload = Str::of('');

        while ($payload->length() !== $bodySize) {
            $payload = $payload->append(
                $connection
                    ->wait()
                    ->values()
                    ->first()
                    ->original()
                    ->toString()
            );
        }

        $message = new Generic($payload);
        $properties = $header
            ->values()
            ->drop(2);

        if ($flagBits & (1 << 15)) {
            [$topLevel, $subType] = explode(
                '/',
                $properties->first()->original()->toString(),
            );
            $message = $message->withContentType(new ContentType(
                $topLevel,
                $subType
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 14)) {
            $message = $message->withContentEncoding(new ContentEncoding(
                $properties->first()->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 13)) {
            $message = $message->withHeaders(
                $properties
                    ->first()
                    ->original()
                    ->reduce(
                        Map::of('string', 'mixed'),
                        static function(Map $carry, string $key, Value $value): Map {
                            return $carry->put(
                                $key,
                                $value->original()
                            );
                        }
                    )
            );
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 12)) {
            $message = $message->withDeliveryMode(
                $properties->first()->original()->value() === DeliveryMode::persistent()->toInt() ?
                    DeliveryMode::persistent() : DeliveryMode::nonPersistent()
            );
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 11)) {
            $message = $message->withPriority(new Priority(
                $properties->first()->original()->value()
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 10)) {
            $message = $message->withCorrelationId(new CorrelationId(
                $properties->first()->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 9)) {
            $message = $message->withReplyTo(new ReplyTo(
                $properties->first()->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 8)) {
            $message = $message->withExpiration(new ElapsedPeriod(
                (int) $properties->first()->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 7)) {
            $message = $message->withId(new Id(
                $properties->first()->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 6)) {
            $message = $message->withTimestamp(
                $properties->first()->original()
            );
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 5)) {
            $message = $message->withType(new Type(
                $properties->first()->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 4)) {
            $message = $message->withUserId(new UserId(
                $properties->first()->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        if ($flagBits & (1 << 3)) {
            $message = $message->withAppId(new AppId(
                $properties->first()->original()->toString(),
            ));
            $properties = $properties->drop(1);
        }

        return $message;
    }
}
