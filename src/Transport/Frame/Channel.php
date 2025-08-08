<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

/**
 * @psalm-immutable
 * @internal
 */
final class Channel
{
    /**
     * @param int<0, 65535> $value
     */
    public function __construct(private int $value)
    {
    }

    /**
     * @return int<0, 65535>
     */
    public function toInt(): int
    {
        return $this->value;
    }
}
