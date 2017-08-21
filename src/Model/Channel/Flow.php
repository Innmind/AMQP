<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Channel;

final class Flow
{
    private $active;

    private function __construct(bool $active)
    {
        $this->active = $active;
    }

    public static function start(): self
    {
        return new self(true);
    }

    public static function stop(): self
    {
        return new self(false);
    }

    public function active(): bool
    {
        return $this->active;
    }
}
