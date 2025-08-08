<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Channel;

/**
 * @psalm-immutable
 */
final class FlowOk
{
    private function __construct(private bool $active)
    {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(bool $active): self
    {
        return new self($active);
    }

    #[\NoDiscard]
    public function active(): bool
    {
        return $this->active;
    }
}
