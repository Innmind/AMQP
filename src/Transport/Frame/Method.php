<?php
declare(strict_types = 1);

namespace Innmind\AMQP\Transport\Frame;

use Innmind\AMQP\Exception\DomainException;

final class Method
{
    private $class;
    private $method;

    public function __construct(int $class, int $method)
    {
        if ($class < 0 || $method < 0) {
            throw new DomainException;
        }

        $this->class = $class;
        $this->method = $method;
    }

    public function class(): int
    {
        return $this->class;
    }

    public function method(): int
    {
        return $this->method;
    }

    public function equals(self $method): bool
    {
        return $this->class === $method->class && $this->method === $method->method;
    }
}
