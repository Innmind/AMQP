<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic\Message;

/**
 * @psalm-immutable
 */
enum Priority
{
    case zero;
    case one;
    case two;
    case three;
    case four;
    case five;
    case six;
    case seven;
    case eight;
    case nine;

    /**
     * @psalm-pure
     */
    public static function lowest(): self
    {
        return self::zero;
    }

    /**
     * @psalm-pure
     */
    public static function highest(): self
    {
        return self::nine;
    }

    /**
     * @psalm-pure
     */
    public static function of(int $value): self
    {
        return match ($value) {
            0 => self::zero,
            1 => self::one,
            2 => self::two,
            3 => self::three,
            4 => self::four,
            5 => self::five,
            6 => self::six,
            7 => self::seven,
            8 => self::eight,
            9 => self::nine,
        };
    }

    public function toInt(): int
    {
        return match ($this) {
            self::zero => 0,
            self::one => 1,
            self::two => 2,
            self::three => 3,
            self::four => 4,
            self::five => 5,
            self::six => 6,
            self::seven => 7,
            self::eight => 8,
            self::nine => 9,
        };
    }
}
