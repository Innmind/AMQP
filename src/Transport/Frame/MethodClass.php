<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
enum MethodClass
{
    case connection;
    case channel;
    case exchange;
    case queue;
    case basic;
    case transaction;

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function maybe(int $value): Maybe
    {
        /** @var Maybe<self> */
        return Maybe::of((match ($value) {
            10 => self::connection,
            20 => self::channel,
            40 => self::exchange,
            50 => self::queue,
            60 => self::basic,
            90 => self::transaction,
            default => null,
        }));
    }

    public function toString(): string
    {
        return match ($this) {
            self::transaction => 'tx',
            default => $this->name,
        };
    }

    /**
     * @return 10|20|30|40|50|60|90
     */
    public function toInt(): int
    {
        return match ($this) {
            self::connection => 10,
            self::channel => 20,
            self::exchange => 40,
            self::queue => 50,
            self::basic => 60,
            self::transaction => 90,
        };
    }
}
