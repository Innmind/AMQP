<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\IO\Frame;

/**
 * @psalm-immutable
 * @internal
 */
enum Type
{
    case method;
    case header;
    case body;
    case heartbeat;

    /**
     * @psalm-pure
     *
     * @return Frame<self>
     */
    public static function frame(int $value): Frame
    {
        return match ($value) {
            1 => Frame::just(self::method),
            2 => Frame::just(self::header),
            3 => Frame::just(self::body),
            8 => Frame::just(self::heartbeat),
            default => Frame::just(self::heartbeat)->filter(static fn() => false), // force fail
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
