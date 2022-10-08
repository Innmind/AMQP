<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

/**
 * @psalm-immutable
 */
enum Type
{
    case method;
    case header;
    case body;
    case heartbeat;

    /**
     * @psalm-pure
     */
    public static function of(int $value): self
    {
        return match ($value) {
            1 => self::method,
            2 => self::header,
            3 => self::body,
            8 => self::heartbeat,
        };
    }

    /**
     * @return 1|2|3|8
     */
    public function toInt(): int
    {
        return match ($this) {
            self::method => 1,
            self::header => 2,
            self::body => 3,
            self::heartbeat => 8,
        };
    }
}
