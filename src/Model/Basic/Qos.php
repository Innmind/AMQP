<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

/**
 * Quality of service
 *
 * Prefetch size : pre-send messages with a payload equal or lower that the specified octets size
 * Prefetch count : the number of messages to pre-send when consuming a queue
 *
 * @psalm-immutable
 */
final class Qos
{
    /** @var int<0, 4294967295> */
    private int $prefetchSize;
    /** @var int<0, 65535> */
    private int $prefetchCount;
    private bool $global = false;

    /**
     * @param int<0, 4294967295> $prefetchSize
     * @param int<0, 65535> $prefetchCount
     */
    private function __construct(int $prefetchSize, int $prefetchCount)
    {
        $this->prefetchSize = $prefetchSize;
        $this->prefetchCount = $prefetchCount;
    }

    /**
     * @psalm-pure
     *
     * @param int<0, 4294967295> $prefetchSize
     * @param int<0, 65535> $prefetchCount
     */
    #[\NoDiscard]
    public static function of(int $prefetchSize, int $prefetchCount): self
    {
        return new self($prefetchSize, $prefetchCount);
    }

    /**
     * Will apply the definition for the whole connection
     *
     * @psalm-immutable
     *
     * @param int<0, 4294967295> $prefetchSize
     * @param int<0, 65535> $prefetchCount
     */
    #[\NoDiscard]
    public static function global(int $prefetchSize, int $prefetchCount): self
    {
        $self = new self($prefetchSize, $prefetchCount);
        $self->global = true;

        return $self;
    }

    /**
     * @return int<0, 4294967295>
     */
    #[\NoDiscard]
    public function prefetchSize(): int
    {
        return $this->prefetchSize;
    }

    /**
     * @return int<0, 65535>
     */
    #[\NoDiscard]
    public function prefetchCount(): int
    {
        return $this->prefetchCount;
    }

    #[\NoDiscard]
    public function isGlobal(): bool
    {
        return $this->global;
    }
}
