<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Basic;

use Innmind\AMQP\Exception\DomainException;

/**
 * Quality of service
 */
final class Qos
{
    private $prefetchSize;
    private $prefetchCount;
    private $global = false;

    public function __construct(int $prefetchSize, int $prefetchCount)
    {
        if ($prefetchSize < 0 || $prefetchCount < 0) {
            throw new DomainException;
        }

        $this->prefetchSize = $prefetchSize;
        $this->prefetchCount = $prefetchCount;
    }

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
