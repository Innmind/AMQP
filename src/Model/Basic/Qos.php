<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Exception\DomainException;

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
    private int $prefetchSize;
    private int $prefetchCount;
    private bool $global = false;

    public function __construct(int $prefetchSize, int $prefetchCount)
    {
        if ($prefetchSize < 0 || $prefetchCount < 0) {
            throw new DomainException("$prefetchSize, $prefetchCount");
        }

        $this->prefetchSize = $prefetchSize;
        $this->prefetchCount = $prefetchCount;
    }

    /**
     * Will apply the definition for the whole connection
     *
     * @psalm-immutable
     */
    public static function global(int $prefetchSize, int $prefetchCount): self
    {
        $self = new self($prefetchSize, $prefetchCount);
        $self->global = true;

        return $self;
    }

    public function prefetchSize(): int
    {
        return $this->prefetchSize;
    }

    public function prefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function isGlobal(): bool
    {
        return $this->global;
    }
}
