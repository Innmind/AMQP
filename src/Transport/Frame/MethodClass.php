<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

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
