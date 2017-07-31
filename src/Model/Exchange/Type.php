<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Model\Exchange;

final class Type
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function direct(): self
    {
        return new self('direct');
    }

    public static function fanout(): self
    {
        return new self('fanout');
    }

    public static function topic(): self
    {
        return new self('topic');
    }

    public static function headers(): self
    {
        return new self('headers');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
