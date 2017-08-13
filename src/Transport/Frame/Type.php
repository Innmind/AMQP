<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

final class Type
{
    private $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function method(): self
    {
        return new self(1);
    }

    public static function header(): self
    {
        return new self(2);
    }

    public static function body(): self
    {
        return new self(3);
    }

    public static function heartbeat(): self
    {
        return new self(4);
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
