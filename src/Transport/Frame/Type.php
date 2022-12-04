<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\Immutable\Maybe;

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
     * @return Maybe<self>
     */
    public static function maybe(int $value): Maybe
    {
        /** @var Maybe<self> */
        return match ($value) {
            1 => Maybe::just(self::method),
            2 => Maybe::just(self::header),
            3 => Maybe::just(self::body),
            8 => Maybe::just(self::heartbeat),
            default => Maybe::nothing(),
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
