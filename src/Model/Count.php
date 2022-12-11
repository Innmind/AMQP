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
    private function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param int<0, max> $value
     */
    public static function of(int $value): self
    {
        return new self($value);
    }

    /**
     * @return int<0, max>
     */
    public function toInt(): int
    {
        return $this->value;
    }
}
