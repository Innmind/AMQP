<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\IO\Frame;

/**
 * @psalm-immutable
 * @internal
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
     * @return Frame<self>
     */
    public static function frame(int $value): Frame
    {
        /** @var Frame<self> */
        return match ($value) {
            10 => Frame::just(self::connection),
            20 => Frame::just(self::channel),
            40 => Frame::just(self::exchange),
            50 => Frame::just(self::queue),
            60 => Frame::just(self::basic),
            90 => Frame::just(self::transaction),
            default => Frame::just(self::basic)->filter(static fn() => false), // force fail
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
