<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 * @internal
 */
enum Version
{
    case v091;
    case v090;

    public function pack(): Str
    {
        return Str::of((match ($this) {
            self::v091 => "AMQP\x00\x00\x09\x01",
            self::v090 => "AMQP\x00\x00\x09\x00",
        }));
    }
}
