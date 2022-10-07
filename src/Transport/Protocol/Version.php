<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Protocol;

use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
enum Version
{
    case v091;
    case v090;

    public function compatibleWith(int $major, int $minor, int $fix): bool
    {
        return match ([$major, $minor, $fix]) {
            [0, 9, 1] => true,
            [0, 9, 0] => true,
            default => false,
        };
    }

    public function pack(): Str
    {
        return Str::of((match ($this) {
            self::v091 => "AMQP\x00\x00\x09\x01",
            self::v090 => "AMQP\x00\x00\x09\x00",
        }));
    }
}
