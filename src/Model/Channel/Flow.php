<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Channel;

/**
 * @psalm-immutable
 */
enum Flow
{
    case start;
    case stop;

    #[\NoDiscard]
    public function active(): bool
    {
        return match ($this) {
            self::start => true,
            self::stop => false,
        };
    }
}
