<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model;

/**
 * @psalm-immutable
 */
final class Count
{
    /** @var int<0, max> */
    private int $value;

    /**
     * @param int<0, max> $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return int<0, max>
     */
    public function toInt(): int
    {
        return $this->value;
    }
}
