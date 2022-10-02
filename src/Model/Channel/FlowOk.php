<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Channel;

/**
 * @psalm-immutable
 */
final class FlowOk
{
    private bool $active;

    public function __construct(bool $active)
    {
        $this->active = $active;
    }

    public function active(): bool
    {
        return $this->active;
    }
}
